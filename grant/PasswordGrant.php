<?php
/**
 * OAuth 2.0 Password grant.
 *
 * @author      Alex Bilbie <hello@alexbilbie.com>
 * @copyright   Copyright (c) Alex Bilbie
 * @license     http://mit-license.org/
 *
 * @link        https://github.com/thephpleague/oauth2-server
 */

namespace Octobro\OAuth2\Grant;

use Auth;
use DateInterval;
use Laravel\Passport\Bridge\User;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Grant\AbstractGrant;

use RainLab\User\Models\Settings as UserSettings;


/**
 * Password grant class.
 */
class PasswordGrant extends AbstractGrant
{
    /**
     * @param RefreshTokenRepositoryInterface $refreshTokenRepository
     */
    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository
    ) {
        $this->setRefreshTokenRepository($refreshTokenRepository);

        $this->refreshTokenTTL = new \DateInterval('P1M');
    }

    /**
     * {@inheritdoc}
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        // Validate request
        $client = $this->validateClient($request);
        
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));
        $user = $this->validateUser($request, $client);
                
        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $user->getIdentifier());

        // Issue and persist new access token
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        $this->getEmitter()->emit(new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken));
        $responseType->setAccessToken($accessToken);
        
        // Issue and persist new refresh token if given
        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($refreshToken !== null) {
            $this->getEmitter()->emit(new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken));
            $responseType->setRefreshToken($refreshToken);
        }
        
        return $responseType;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ClientEntityInterface  $client
     *
     * @throws OAuthServerException
     *
     * @return UserEntityInterface
     */
    protected function validateUser(ServerRequestInterface $request, ClientEntityInterface $client)
    {
        $login = $this->getRequestParameter('login', $request);

        if (!\is_string($login)) {
            throw OAuthServerException::invalidRequest('login');
        }

        $password = $this->getRequestParameter('password', $request);

        if (!\is_string($password)) {
            throw OAuthServerException::invalidRequest('password');
        }
        
        $credentials = [
            'login'    => $login,
            'password' => $password,
        ];

        $user = $this->getUserEntity(
            $login,
            $password,
            $this->getIdentifier(),
            $client
        );
        
        if ($user instanceof UserEntityInterface === false) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::USER_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidCredentials();
        }
        
        return $user;
    }

    private function getUserEntity($login, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $provider = config('auth.guards.api.provider');

        if (is_null($model = config('auth.providers.'.$provider.'.model'))) {
            throw new RuntimeException('Unable to determine authentication model from configuration.');
        }

        $credentials = [
            'login'    => $login,
            'password' => $password
        ];

        $user = Auth::authenticate($credentials, $this->useRememberLogin());

        if (is_null($user)) {
            return;
        }

        return new User($user->getAuthIdentifier());
    }

    /**
     * useRememberLogin returns true if persistent authentication should be used.
     */
    protected function useRememberLogin(): bool
    {
        switch ($this->rememberLoginMode()) {
            case UserSettings::REMEMBER_ALWAYS:
                return true;

            case UserSettings::REMEMBER_NEVER:
                return false;

            case UserSettings::REMEMBER_ASK:
                return (bool) post('remember', false);
        }
    }

    /**
     * Returns the login remember mode.
     */
    public function rememberLoginMode()
    {
        return UserSettings::get('remember_login', UserSettings::REMEMBER_ALWAYS);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'password';
    }
}

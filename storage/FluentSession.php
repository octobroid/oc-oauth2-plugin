<?php namespace Octobro\OAuth2\Storage;

use Carbon\Carbon, Cache;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\SessionInterface;

/**
 * This is the fluent session class.
 */
class FluentSession extends AbstractFluentAdapter implements SessionInterface
{
    /**
     * Get a session from it's identifier.
     *
     * @param string $sessionId
     *
     * @return \League\OAuth2\Server\Entity\SessionEntity
     */
    public function get($sessionId)
    {
        $result = $this->getConnection()->table('oauth_sessions')
                    ->where('oauth_sessions.id', $sessionId)
                    ->first();

        if (is_null($result)) {
            return;
        }

        return (new SessionEntity($this->getServer()))
               ->setId($result->id)
               ->setOwner($result->owner_type, $result->owner_id);
    }

    /**
     * Get a session from an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $accessToken The access token
     *
     * @return \League\OAuth2\Server\Entity\SessionEntity
     */
    public function getByAccessToken(AccessTokenEntity $accessToken)
    {
        $sessionId = $this->getCacheSessionId($accessToken->getId());

        if($this->checkCacheSession($sessionId)){
            $result = (object) $this->getCacheSession($sessionId);
        }else{
            $result = $this->getConnection()
                ->table('oauth_sessions')
                ->select('oauth_sessions.*')
                ->join('oauth_access_tokens', 'oauth_sessions.id', '=', 'oauth_access_tokens.session_id')
                ->where('oauth_access_tokens.id', $accessToken->getId())
                ->first();
        }

        $this->setCacheOwnerId($result->owner_id, $accessToken->getId());

        if (is_null($result)) {
            return;
        }

        return (new SessionEntity($this->getServer()))
               ->setId($result->id)
               ->setOwner($result->owner_type, $result->owner_id);
    }

    /**
     * Get a session's scopes.
     *
     * @param \League\OAuth2\Server\Entity\SessionEntity
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(SessionEntity $session)
    {
        // TODO: Check this before pushing
        $result = $this->getConnection()->table('oauth_session_scopes')
                  ->select('oauth_scopes.*')
                  ->join('oauth_scopes', 'oauth_session_scopes.scope_id', '=', 'oauth_scopes.id')
                  ->where('oauth_session_scopes.session_id', $session->getId())
                  ->get();

        $scopes = [];

        foreach ($result as $scope) {
            $scopes[] = (new ScopeEntity($this->getServer()))->hydrate([
                'id' => $scope->id,
                'description' => $scope->description,
            ]);
        }

        return $scopes;
    }

    /**
     * Create a new session.
     *
     * @param string $ownerType Session owner's type (user, client)
     * @param string $ownerId Session owner's ID
     * @param string $clientId Client ID
     * @param string $clientRedirectUri Client redirect URI (default = null)
     *
     * @return int The session's ID
     */
    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null)
    {
        $credentials = [
            'client_id' => $clientId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'client_redirect_uri' => $clientRedirectUri,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        $result = $this->getConnection()
        ->table('oauth_sessions')
        ->insertGetId($credentials);

        $this->setCacheSession($result, $credentials);

        return $result;
    }

    /**
     * Associate a scope with a session.
     *
     * @param \League\OAuth2\Server\Entity\SessionEntity $session
     * @param \League\OAuth2\Server\Entity\ScopeEntity $scope The scopes ID might be an integer or string
     *
     * @return void
     */
    public function associateScope(SessionEntity $session, ScopeEntity $scope)
    {
        $this->getConnection()->table('oauth_session_scopes')->insert([
            'session_id' => $session->getId(),
            'scope_id' => $scope->getId(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Get a session from an auth code.
     *
     * @param \League\OAuth2\Server\Entity\AuthCodeEntity $authCode The auth code
     *
     * @return \League\OAuth2\Server\Entity\SessionEntity
     */
    public function getByAuthCode(AuthCodeEntity $authCode)
    {
        $result = $this->getConnection()->table('oauth_sessions')
            ->select('oauth_sessions.*')
            ->join('oauth_auth_codes', 'oauth_sessions.id', '=', 'oauth_auth_codes.session_id')
            ->where('oauth_auth_codes.id', $authCode->getId())
            ->first();

        if (is_null($result)) {
            return;
        }

        return (new SessionEntity($this->getServer()))
               ->setId($result->id)
               ->setOwner($result->owner_type, $result->owner_id);
    }

    /**
     * Set cache session.
     *
     * @param $token
     * @param $value
     * @param $expireTime
     *
     * @return void
     */
    protected function setCacheSession($value, $credentials)
    {
        $cacheName = sprintf('session_%s', bin2hex($value));
        Cache::put($cacheName, array_merge(['id' => $value], $credentials), 10080);
    }

    /**
     * Get cache session.
     *
     * @param $value
     *
     * @return void
     */
    protected function getCacheSession($value)
    {
        $cacheName = sprintf('session_%s', bin2hex($value));
        return Cache::get($cacheName);
    }

    /**
     * Check cache session.
     *
     * @param $value
     *
     * @return void
     */
    protected function checkCacheSession($value)
    {
        $cacheName = sprintf('session_%s', bin2hex($value));
        return Cache::has($cacheName);
    }

    /**
     * Get cache session id.
     *
     * @param $token
     *
     * @return void
     */
    protected function getCacheSessionId($token)
    {
        $cacheName = sprintf('session_token_%s', $token);
        return Cache::get($cacheName);
    }

    protected function setCacheOwnerId($value, $token)
    {
        $cacheName = sprintf('owner_id_token_%s', $token);
        return Cache::put($cacheName, $value, 10080);
    }
}

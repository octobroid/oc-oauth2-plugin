<?php namespace Octobro\OAuth2\Storage;

use Carbon\Carbon, Cache;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AccessTokenInterface;

/**
 * This is the fluent access token class.
 */
class FluentAccessToken extends AbstractFluentAdapter implements AccessTokenInterface
{
    /**
     * Get an instance of Entities\AccessToken.
     *
     * @param string $token The access token
     *
     * @return null|AbstractTokenEntity
     */
    public function get($token)
    {
        if($this->checkCacheToken($token)){
            $result = (object) $this->getCacheToken($token);
        }else{
            $result = $this->getConnection()
            ->table('oauth_access_tokens')
            ->where('oauth_access_tokens.id', $token)
            ->first();
        }

        if (is_null($result)) {
            return;
        }

        return (new AccessTokenEntity($this->getServer()))
               ->setId($result->id)
               ->setExpireTime((int) $result->expire_time);
    }

    /*
    public function getByRefreshToken(RefreshTokenEntity $refreshToken)
    {
        $result = $this->getConnection()->table('oauth_access_tokens')
                ->select('oauth_access_tokens.*')
                ->join('oauth_refresh_tokens', 'oauth_access_tokens.id', '=', 'oauth_refresh_tokens.access_token_id')
                ->where('oauth_refresh_tokens.id', $refreshToken->getId())
                ->first();

        if (is_null($result)) {
            return null;
        }

        return (new AccessTokenEntity($this->getServer()))
               ->setId($result->id)
               ->setExpireTime((int)$result->expire_time);
    }
    */

    /**
     * Get the scopes for an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(AccessTokenEntity $token)
    {
        $result = $this->getConnection()->table('oauth_access_token_scopes')
                ->select('oauth_scopes.*')
                ->join('oauth_scopes', 'oauth_access_token_scopes.scope_id', '=', 'oauth_scopes.id')
                ->where('oauth_access_token_scopes.access_token_id', $token->getId())
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
     * Creates a new access token.
     *
     * @param string $token The access token
     * @param int $expireTime The expire time expressed as a unix timestamp
     * @param string|int $sessionId The session ID
     *
     * @return \League\OAuth2\Server\Entity\AccessTokenEntity
     */
    public function create($token, $expireTime, $sessionId)
    {
        $credentials = [
            'id' => $token,
            'expire_time' => $expireTime,
            'session_id' => $sessionId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        $result = $this->getConnection()
        ->table('oauth_access_tokens')
        ->insert($credentials);

        $this->setCacheToken($token, $credentials, $expireTime);
        $this->setCacheSessionToken($sessionId, $token);

        return (new AccessTokenEntity($this->getServer()))
               ->setId($token)
               ->setExpireTime((int) $expireTime);
    }

    /**
     * Associate a scope with an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     * @param \League\OAuth2\Server\Entity\ScopeEntity $scope The scope
     *
     * @return void
     */
    public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
    {
        $this->getConnection()->table('oauth_access_token_scopes')->insert([
            'access_token_id' => $token->getId(),
            'scope_id' => $scope->getId(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Delete an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token to delete
     *
     * @return void
     */
    public function delete(AccessTokenEntity $token)
    {
        $this->getConnection()->table('oauth_access_tokens')
        ->where('oauth_access_tokens.id', $token->getId())
        ->delete();
    }


    /**
     * Set cache access token.
     *
     * @param $token
     * @param $value
     * @param $expireTime
     *
     * @return void
     */
    protected function setCacheToken($token, $credentials, $expireTime)
    {
        $cacheName   = sprintf('user_%s_%s_%s', ...array_reverse(str_split($token, 10)));
        Cache::put($cacheName, $credentials, (int) round($expireTime / 60000));
    }

    /**
     * Get cache access token.
     *
     * @param $token
     *
     * @return void
     */
    protected function getCacheToken($token)
    {
        $cacheName = sprintf('user_%s_%s_%s', ...array_reverse(str_split($token, 10)));
        return Cache::get($cacheName);
    }

    /**
     * Check cache access token.
     *
     * @param $token
     *
     * @return void
     */
    protected function checkCacheToken($token)
    {
        $cacheName = sprintf('user_%s_%s_%s', ...array_reverse(str_split($token, 10)));
        return Cache::has($cacheName);
    }

    /**
     * set cache session token.
     *
     * @param $token
     *
     * @return void
     */
    protected function setCacheSessionToken($sessionId, $token)
    {
        $cacheName = sprintf('session_token_%s', $token);
        return Cache::put($cacheName, $sessionId, 10000);
    }
}

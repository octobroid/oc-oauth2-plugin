<?php namespace Octobro\OAuth2\Classes;

use Laravel\Passport\Passport;
use Illuminate\Auth\RequestGuard;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\PassportUserProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\AuthorizationServer;
use Octobro\Oauth2\Classes\PasswordGrant;

class OAuth2ServerServiceProvider extends ServiceProvider
{
    /**
     * @var GrantTypeInterface[]
     */
    protected $enabledGrantTypes = [];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        
        $config = $this->app['config']->get('passport');
        if (array_key_exists('grant_types', $config)) {
            foreach ($config['grant_types'] as $grantIdentifier => $grantParams) {
                app(AuthorizationServer::class)->enableGrantType(
                    $this->makeGrantType($grantParams['class']), Passport::tokensExpireIn()
                );
            }
        }
        
        Passport::routes();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }

    protected function makeGrantType($grantClass) 
    {
        $grant = new $grantClass(
            $this->app->make(RefreshTokenRepository::class)
        );

        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }

    /**
     * Make an instance of the token guard.
     *
     * @param  array  $config
     * @return \Illuminate\Auth\RequestGuard
     */
    public function makeGuard(array $config)
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new TokenGuard(
                $this->app->make(ResourceServer::class),
                new PassportUserProvider(new ApiUserProvider, 'users'),
                $this->app->make(TokenRepository::class),
                $this->app->make(ClientRepository::class),
                $this->app->make('encrypter')
            ))->user($request);
        }, $this->app['request']);
    }
}

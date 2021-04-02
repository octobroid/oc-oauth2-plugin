<?php namespace Octobro\OAuth2\Classes;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Auth\RequestGuard;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\PassportUserProvider;
use League\OAuth2\Server\ResourceServer;


class OAuth2ServerServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Passport::ignoreMigrations();
        Passport::routes();

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));
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

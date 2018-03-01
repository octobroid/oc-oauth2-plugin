<?php namespace Octobro\OAuth2;

use App;
use Auth;
use Config;
use Authorizer;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Illuminate\Foundation\AliasLoader;
use Octobro\API\Classes\ApiController;

class Plugin extends PluginBase
{
    public $require = ['Octobro.API', 'RainLab.User'];

    public function boot()
    {
        // Register oAuth
        App::register('\Octobro\OAuth2\Storage\FluentStorageServiceProvider');
        App::register('\Octobro\OAuth2\Classes\OAuth2ServerServiceProvider');

        // Add alias
        $alias = AliasLoader::getInstance();
        $alias->alias('Authorizer', '\Octobro\OAuth2\Facades\Authorizer');

        // Add oauth middleware
        // $this->middleware(\Octobro\OAuth2\Middleware\OAuthExceptionHandlerMiddleware::class);

        // Add oauth route middleware
        app('router')->aliasMiddleware('oauth' , \Octobro\OAuth2\Middleware\OAuthMiddleware::class);
        app('router')->aliasMiddleware('oauth-user' , \Octobro\OAuth2\Middleware\OAuthUserOwnerMiddleware::class);
        app('router')->aliasMiddleware('oauth-client' , \Octobro\OAuth2\Middleware\OAuthClientOwnerMiddleware::class);
        app('router')->aliasMiddleware('check-authorization-params', \Octobro\OAuth2\Middleware\CheckAuthCodeRequestMiddleware::class);

        ApiController::extend(function($controller) {
            $controller->addDynamicMethod('getUser', function() use ($controller) {
                
                if (Auth::getUser()) return Auth::getUser();

                $userId = Authorizer::getResourceOwnerId();

                if ($userId) {
                    $user = User::find($userId);

                    Auth::login($user);

                    return $user;
                }
            });
        });
    }
}

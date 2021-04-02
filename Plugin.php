<?php namespace Octobro\OAuth2;

use App;
use Auth;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Octobro\API\Classes\ApiController;
use Octobro\OAuth2\Classes\OAuth2ServerServiceProvider;

class Plugin extends PluginBase
{
    public $require = ['Octobro.API', 'RainLab.User'];

    public function boot()
    {
        App::register(\Laravel\Passport\PassportServiceProvider::class);
        App::register(OAuth2ServerServiceProvider::class);

        // Add oauth route middleware
        app('router')->aliasMiddleware('oauth' , \Octobro\OAuth2\Middleware\OAuthMiddleware::class);

        User::extend(function ($model) {
            if (!$model->isClassExtendedWith('Octobro.OAuth2.Behaviors.Tokenable')) {
                $model->implement[] = 'Octobro.OAuth2.Behaviors.Tokenable';
            }
        });

        ApiController::extend(function($controller) {
            $controller->addDynamicMethod('getUser', function() use ($controller) {
                return Auth::getUser();
            });
        });
    }

    public function registerSchedule($schedule)
    {
        $schedule->command('passport:purge')->hourly();
    }

}

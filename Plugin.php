<?php namespace Sv\OAuth2;

use App;
use Auth;
use RainLab\User\Models\User;
use System\Classes\PluginBase;
use Sv\API\Classes\ApiController;
use Sv\OAuth2\Classes\OAuth2ServerServiceProvider;

class Plugin extends PluginBase
{
    public $require = ['Sv.API', 'RainLab.User'];

    public function boot()
    {
        App::register(\Laravel\Passport\PassportServiceProvider::class);
        App::register(OAuth2ServerServiceProvider::class);

        // Add oauth route middleware
        app('router')->aliasMiddleware('oauth' , \Sv\OAuth2\Middleware\OAuthMiddleware::class);

        User::extend(function ($model) {
            if (!$model->isClassExtendedWith('Sv.OAuth2.Behaviors.Tokenable')) {
                $model->implement[] = 'Sv.OAuth2.Behaviors.Tokenable';
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

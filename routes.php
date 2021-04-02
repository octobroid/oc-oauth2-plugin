<?php

    Route::group([
        'domain' => env('API_DOMAIN'),
        'prefix' => env('API_PREFIX', 'api') .'/v1',
        'namespace' => 'Octobro\OAuth2\Controllers',
        'middleware' => 'cors'
        ], function() {
            
            // Legacy URL
            Route::post('auth/access_token', [
                'uses'       => '\Laravel\Passport\Http\Controllers\AccessTokenController@issueToken',
                'middleware' => 'throttle',
            ]);

            Route::post('auth/register', 'Auth@register');
            Route::post('auth/forgot', 'Auth@forgot');

            Route::group(['middleware' => 'oauth'], function() {
                //
                Route::get('me', 'Me@show');
                Route::put('me', 'Me@update');
            });
    });

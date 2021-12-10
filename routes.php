<?php

    Route::group([
        'domain' => env('API_DOMAIN'),
        'prefix' => env('API_PREFIX', 'api') .'/v1',
        'namespace' => 'Sv\OAuth2\Controllers',
        ], function() {

            Route::post('auth/access_token', 'Auth@accessToken');
            Route::post('auth/register', 'Auth@register');
            Route::post('auth/forgot', 'Auth@forgot');

            Route::group(['middleware' => 'oauth-users'], function() {
                Route::get('me', 'Me@show');
                Route::put('me', 'Me@update');
            });
    });

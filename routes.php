<?php

    Route::group([
        'domain' => env('API_DOMAIN'),
        'prefix' => env('API_PREFIX', 'api') .'/v1',
        'namespace' => 'Octobro\OAuth2\Controllers',
        ], function() {
            
            if (!env('OCTOBRO_OAUTH2_CUSTOMLOGIN', false)) {
                Route::post('auth/access_token', 'Auth@accessToken');
            }
            Route::post('auth/register', 'Auth@register');
            Route::post('auth/forgot', 'Auth@forgot');
            Route::post('auth/reset', 'Auth@reset');

            Route::group(['middleware' => 'oauth'], function() {
                Route::get('me', 'Me@show');
                Route::put('me', 'Me@update');
                Route::delete('me', 'Me@destroy');
                Route::delete('me/avatar', 'Me@destroyAvatar');
            });
    });

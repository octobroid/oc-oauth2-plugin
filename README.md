# OAuth 2.0 Plugin for OctoberCMS

It's a plugin for OctoberCMS for you that want to create an OAuth 2 API for your RainLab.User plugin in easy way.

## Features

- [OAuth 2.0](https://oauth.net/2/) server ready
- [RainLab.User](http://octobercms.com/plugin/rainlab-user) plugin integration

## Installation

1. [**Download API Framework plugin**](https://github.com/octobroid/oc-api-plugin/archive/master.zip) and put to plugins directory (`plugins/sv/api`).
1. [**Download**](https://github.com/octobroid/oc-oauth2-plugin/archive/master.zip) this plugin and put to plugins directory (`plugins/sv/oauth2`).
2. Run `composer update` on your project root directory.
3. Run `php artisan october:up`.

> Tips: if you want to follow this plugin, you can use this plugin as a submodule on your git project.


## Usage

### Password Authentication

This plugin has a built-in user authentication using password. You can create your own authentication using this plugin also.

To get started, the authentication is by creating an HTTP POST request to `http://example.com/api/v1/auth/access_token` with these body parameters:

| Param         | Description                                                          | Example                  |
|---------------|----------------------------------------------------------------------|--------------------------|
| client_id     | It's a key for an app. We generate it when you installed this plugin | `818492836130`           |
| client_secret | Key for selected app (make this one secret)                          | `dfxaksfhtokudiaqpieojx` |
| grant_type    | Authentication method. For this plugin only `password` is available  | `password`               |
| username      | Username/email from user                                             | `myusername`             |
| password      | Password from user                                                   | `mypassword`             |

The response will be:

```json
{
  "access_token": "O6qxvTwllfsoeTJ7dbpmaa5Vt7UA9a6GlrwlAgWd",
  "token_type": "Bearer",
  "expires_in": 604800
}
```
Use this access token for your next protected request by put it on header:

```
Authorization: Bearer {YOUR_ACCESS_TOKEN}
```

### Authentication Middleware

On your project plugin API, you might want to use this middleware for authenticating the user.

On your `routes.php` you can define the API route and adding the `oauth` middleware on it.

```php
Route::group(['middleware' => 'oauth'], function() {

	//
	// Your protected resources should be here.
	// This is example routes below
	//

    Route::get('orders', 'Orders@index');
    Route::post('orders', 'Orders@store');

});
```

### Getting User Data

On your `Orders.php` file, you can check the user and get the data like this.

```php
<?php namespace Foo\Bar\ApiControllers;

use ApplicationException;
use Sv\API\Classes\ApiController;
use Foo\Bar\Transformers\OrderTransformer;

class Orders extends ApiController
{
    public function index()
    {
        // Get the user data
        $user = $this->getUser();

        if (!$user) {
            throw new ApplicationException('User not found.');
        }

        return $this->respondwithCollection($user->orders, new OrderTransformer);
    }

    public function store($id)
    {
        // Get the user data
        $user = $this->getUser();

        // Your custom procedure
    }
}
```

## Extending Plugins

Need to extend the plugin? We can just add some lines to add the fields of data, or even creating or manipulating includes query.

In this example we want to extend `UserTransformer.php`.

### Adding Fields

```php
// Add this on your plugin boot() method

UserTransformer::extend(function($transformer) {

    // Add field one by one
    $transformer->addField('avatar', function($user) use ($transformer) {
        return $transformer->image($user->avatar);
    });

    // Add field based on object attribute
    $transformer->addField('is_banned');

    // Wanna add more fields based on attributes?
    // You can put it all together
    $transformer->addFields(['updated_at', 'verified_at']);
});
```


### Adding Includes

```php
// Add this on your plugin boot() method

UserTransformer::extend(function($transformer) {

    // For example it has reviews relation
    $transformer->addInclude('orders', function($user) use ($transfomer) {
        return $transformer->collection($user->orders, new OrderTransformer);
    });

});
```


## License

The OctoberCMS platform is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

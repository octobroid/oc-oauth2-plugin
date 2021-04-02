<?php namespace Octobro\OAuth2\Middleware;

use Illuminate\Auth\RequestGuard;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Guards\TokenGuard;
use Octobro\OAuth2\Classes\ApiUserProvider;
use Octobro\OAuth2\Classes\OAuth2ServerServiceProvider;

/**
 * This is the oauth middleware class.
 */
class OAuthMiddleware
{
    public static function create()
    {
        $serviceProvider = new OAuth2ServerServiceProvider(app());

        $serviceProvider->register();
        
        return $serviceProvider->makeGuard(app()['config']['auth.guards']['api']);
    }

    public static function handle($request, $next)
    {
        $guard = self::create();
        if ($guard->user($request)) {
            return $next($request);
        } else {
            return response()->json([
                "error" => [
                    "code" => "UNAUTHORIZED",
                    "http_code" => 401,
                    "message" => "The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed. Check the \"access token\" parameter."
                ]
            ], 401);
        }
    }

}

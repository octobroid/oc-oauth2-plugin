<?php namespace Sv\OAuth2\Middleware;

use Sv\OAuth2\Classes\OAuth2ServerServiceProvider;

/**
 * This is the oauth middleware class.
 */
class OAuthUsersMiddleware
{
    public static function create()
    {
        $serviceProvider = new OAuth2ServerServiceProvider(app());

        $serviceProvider->register();

        return $serviceProvider->makeGuard(app()['config']['auth.guards']['api_users']);
    }

    public static function handle($request, $next)
    {
        $guard = self::create();

        try {
            if (!$guard->user($request)) {
                return self::respondWithError('Invalid user credential.');
            }
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $e) {
            return self::respondWithError(array_get($e->getPayload(), 'error_description', 'Unauthorized'), $e->getHttpStatusCode());
        } catch (\Exception $e) {
            return self::respondWithError($e->getMessage());
        }

        return $next($request);
    }

    protected static function respondWithError($message, $httpCode = 401, $code = 'UNAUTHORIZED')
    {
        return response()->json([
            "error" => [
                "code"      => $code,
                "http_code" => $httpCode,
                "message"   => $message,
            ]
        ], $httpCode);
    }
}

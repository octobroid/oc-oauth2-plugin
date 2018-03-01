<?php namespace Octobro\OAuth2\Classes;

use Auth;

class PasswordGrantVerifier
{
    public function verify($username, $password)
    {
        $credentials = [
            'login'    => $username,
            'password' => $password,
        ];

        if ($user = Auth::authenticate($credentials)) {
            return $user->id;
        }

        return false;
    }
}
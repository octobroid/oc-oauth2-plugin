<?php namespace Octobro\OAuth2\Classes;

use Auth, Lang;
use October\Rain\Auth\AuthException;

class PasswordGrantVerifier
{
    public function verify($username, $password)
    {
        $credentials = [
            'login'    => $username,
            'password' => $password,
        ];

        try {
            if ($user = Auth::authenticate($credentials)) {
                return $user->id;
            }
        } catch (AuthException $th) {
            throw new AuthException($this->getInvalidCredentialMessage() ?: $th->getMessage());
        }

        return false;
    }

    public function getInvalidCredentialMessage()
    {
        $code_lang = 'octobro.oauth2::lang.auth.invalid_credential';
        $message   = Lang::get($code_lang);

        if($message == $code_lang){
            return null;
        }

        return $message;
    }
}
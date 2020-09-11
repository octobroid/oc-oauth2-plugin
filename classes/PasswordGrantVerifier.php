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
            throw new AuthException($this->getInvalidCredentialMessage($th->getMessage()));
        }

        return false;
    }

    public function getInvalidCredentialMessage($throw_message)
    {
        if (strrpos($throw_message,'hashed credential') !== false) {
            $code_lang = 'octobro.oauth2::lang.auth.invalid_credential';
            $message   = Lang::get($code_lang);

            if($message == $code_lang){
                return $throw_message;
            }

            return $message;
        } elseif (strrpos($throw_message,'user was not found') !== false) {
            $code_lang = 'octobro.oauth2::lang.auth.not_found';
            $message   = Lang::get($code_lang);

            if($message == $code_lang){
                return $throw_message;
            }

            return $message;
        } elseif (strrpos($throw_message,'suspended') !== false) {
            $code_lang = 'octobro.oauth2::lang.auth.suspended';
            $message   = Lang::get($code_lang);

            if($message == $code_lang){
                return $throw_message;
            }

            return $message;
        } elseif (strrpos($throw_message,'not activated') !== false) {
            $code_lang = 'octobro.oauth2::lang.auth.inactive';
            $message   = Lang::get($code_lang);

            if($message == $code_lang){
                return $throw_message;
            }

            return $message;
        } else {
            return $message;
        }
    }
}
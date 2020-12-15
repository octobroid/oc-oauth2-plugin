<?php namespace Octobro\OAuth2\Controllers;

use Db;
use Validator;
use Exception;
use Authorizer;
use Auth as AuthBase;
use ValidationException;
use Event;
use Mail;
use Lang;
use Octobro\API\Classes\ApiController;

class Auth extends ApiController
{
    public function accessToken()
    {
        try {
            /**
            * Extensibility
            */
            Event::fire('octobro.oauth2.beforeAccessToken', [
                $this->data
            ]);

            return $this->respondWithArray((Authorizer::issueAccessToken()));
        } catch (Exception $e) {
            return $this->errorWrongArgs($this->getInvalidCredentialMessage($e->getMessage()));
        }
    }

    public function register()
    {
        try {

           Db::beginTransaction();
           /*
            * Validate input
            */
           $data = $this->data;

           if (!array_key_exists('password_confirmation', $data)) {
               $data['password_confirmation'] = post('password');
           }

           $rules = [
               'name'     => 'required',
               'email'    => 'required|email|between:6,255',
               'password' => 'required|between:4,255',
           ];

           /**
            * Extensibility
            */
           Event::fire('octobro.oauth2.beforeRegister', [$data]);

           $validation = Validator::make($data, $rules);
           if ($validation->fails()) {
               throw new ValidationException($validation);
           }

           // Register, no need activation
           $user = AuthBase::register($data, true);

           /**
            * Extensibility
            */
           Event::fire('octobro.oauth2.register', [$user, $data]);

           Db::commit();

           return $this->respondWithArray(Authorizer::issueAccessToken());

       } catch (Exception $e) {
           Db::rollBack();
           return $this->errorWrongArgs($e->getMessage());
       }
    }

    public function forgot()
    {
        /*
        * Validate input
        */
        $data = $this->data;

        $rules = [
            'email' => 'required|email|between:6,255'
        ];

        $validation = Validator::make($data, $rules);
        if ($validation->fails()) {
            throw new ValidationException($validation);
        }

        $email = array_get($data, 'email');

        try {
            $user = \RainLab\User\Models\User::findByEmail($email);

            if (!$user || $user->is_guest) {
                throw new \ApplicationException(Lang::get('rainlab.user::lang.account.invalid_user'));
            }

            $code = implode('!', [$user->id, $user->getResetPasswordCode()]);

            $paramUrl = sprintf('?%s', http_build_query([
                'code' => $code
            ]));

            $link = \Cms\Classes\Page::url('mobile-view/reset-password') . $paramUrl;

            $mail_data = [
                'name' => $user->name,
                'link' => $link,
                'code' => $code
            ];

            Mail::queue('rainlab.user::mail.restore', $mail_data, function($message) use ($user) {
                $message->to($user->email, $user->full_name);
            });
        } catch (\ApplicationException $th) {
            
        }
        
        return $this->respondWithItem($data, function(){
            return [
                'code' => '200',
                'message' => Lang::get('octobro.oauth2::lang.auth.forgot_email'),
            ];
        });
        
    }

    protected function getInvalidCredentialMessage($throw_message)
    {
        if (strrpos($throw_message,'authentication failed') !== false) {
            $code_lang = 'octobro.oauth2::lang.auth.client_authentication_failed';
            $message   = Lang::get($code_lang);

            if($message == $code_lang){
                return $throw_message;
            }

            return $message;
        } else {
            return $throw_message;
        }
    }
}

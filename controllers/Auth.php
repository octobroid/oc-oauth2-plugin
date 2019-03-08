<?php namespace Octobro\OAuth2\Controllers;

use Db;
use Validator;
use Exception;
use Authorizer;
use Auth as AuthBase;
use ValidationException;
use Event;
use Octobro\API\Classes\ApiController;

class Auth extends ApiController
{
    public function accessToken()
    {
        try {
            return $this->respondWithArray((Authorizer::issueAccessToken()));
        } catch (Exception $e) {
            return $this->errorWrongArgs($e->getMessage());
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

           Event::fire('octobro.oauth2.beforeRegister', [$data]);

           $validation = Validator::make($data, $rules);
           if ($validation->fails()) {
               throw new ValidationException($validation);
           }

           // Register, no need activation
           $user = AuthBase::register($data, true);

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
    }

}

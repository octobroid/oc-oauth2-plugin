<?php namespace Octobro\OAuth2\Controllers;

use Db;
use Request;
use Validator;
use Exception;
use Auth as AuthBase;
use ValidationException;
use ApplicationException;
use Event;
use Mail;
use Lang;
use League\Fractal\Manager;
use League\OAuth2\Server\AuthorizationServer;
use Octobro\API\Classes\ApiController;

use Octobro\Oauth2\Classes\AuthServiceProvider;
use Octobro\OAuth2\Transformers\UserTransformer;

use Laminas\Diactoros\Response as Psr7Response;

use RainLab\User\Models\User as UserModel;
use RainLab\User\Models\Settings as UserSettings;

class Auth extends ApiController
{

    public function __construct(Manager $fractal, AuthorizationServer $server)
    {
        $this->server = $server;
        parent::__construct($fractal);
    }

    public function accessToken(\Psr\Http\Message\ServerRequestInterface $request)
    {
        try {
            return $this->server->respondToAccessTokenRequest($request, new Psr7Response);
        } catch (Exception $e) {
            return $this->errorWrongArgs($e->getMessage());
        }
    }

    public function register(\Psr\Http\Message\ServerRequestInterface $request)
    {
        try {

            Db::beginTransaction();

            if (!$this->canRegister()) {
                throw new ApplicationException(Lang::get(/*Registrations are currently disabled.*/'rainlab.user::lang.account.registration_disabled'));
            }

            if ($this->isRegisterThrottled()) {
                throw new ApplicationException(Lang::get(/*Registration is throttled. Please try again later.*/'rainlab.user::lang.account.registration_throttled'));
            }
            
           /*
            * Validate input
            */
            $data = $this->data;

            if (!array_key_exists('password_confirmation', $data)) {
                $data['password_confirmation'] = post('password');
            }

            $rules = (new UserModel)->rules;

            if ($this->loginAttribute() !== UserSettings::LOGIN_USERNAME) {
                unset($rules['username']);
            }

            /**
            * Extensibility
            */
            Event::fire('octobro.oauth2.beforeRegister', [$data]);

            $validation = Validator::make($data, $rules);
            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            /*
             * Record IP address
             */
            if ($ipAddress = Request::ip()) {
                $data['created_ip_address'] = $data['last_ip_address'] = $ipAddress;
            }
            
            // Register
            $requireActivation = UserSettings::get('require_activation', true);
            $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
            $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;
            
            $user = AuthBase::register($data, $automaticActivation);

            /*
             * Activation is by the user, send the email
             */
            if ($userActivation) {
                $this->sendActivationEmail($user);
            }

            /**
            * Extensibility
            */
            Event::fire('octobro.oauth2.register', [$user, $data]);

            Db::commit();

            /*
             * Automatically activated or not required, log the user in
             */
            if ($automaticActivation || !$requireActivation) {
                if (post('client_id') && post('client_secret')) {
                    if ($this->loginAttribute() == 'username') {
                        $request = $request->withParsedBody(array_merge($request->getParsedBody(), [
                            'grant_type' => 'password',
                            'login' => $user->username,
                        ]));
                    } else {
                        $request = $request->withParsedBody(array_merge($request->getParsedBody(), [
                            'grant_type' => 'password',
                            'login' => $user->email,
                        ]));
                    }
    
                    return $this->server->respondToAccessTokenRequest($request, new Psr7Response);
                }
            }
            
            return $this->respondWithItem($user, new UserTransformer);

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

    /**
     * Sends the activation email to a user
     * @param  User $user
     * @return void
     */
    protected function sendActivationEmail($user)
    {        
        $code = implode('!', [$user->id, $user->getActivationCode()]);

        $link = $this->makeActivationUrl($code);

        $data = [
            'name' => $user->name,
            'link' => $link,
            'code' => $code
        ];

        Mail::send('rainlab.user::mail.activate', $data, function($message) use ($user) {
            $message->to($user->email, $user->name);
        });
    }

    /**
     * Returns a link used to activate the user account.
     * @return string
     */
    protected function makeActivationUrl($code)
    {
        if (env('APP_URL')) {
            return env('APP_URL').'/activate?activate='.$code;
        } else {
            return url()->current().'/activate?activate='.$code;
        }
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

    /**
     * Flag for allowing registration, pulled from UserSettings
     */
    public function canRegister()
    {
        return UserSettings::get('allow_registration', true);
    }

    /**
     * Returns the login model attribute.
     */
    public function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    /**
     * Returns true if user is throttled.
     * @return bool
     */
    protected function isRegisterThrottled()
    {
        if (!UserSettings::get('use_register_throttle', false)) {
            return false;
        }

        return UserModel::isRegisterThrottled(Request::ip());
    }
}

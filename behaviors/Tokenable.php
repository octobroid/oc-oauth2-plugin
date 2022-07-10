<?php namespace Octobro\OAuth2\Behaviors;

use Laravel\Passport\Passport;
use Laravel\Passport\HasApiTokens;
use Illuminate\Container\Container;
use Laravel\Passport\PersonalAccessTokenFactory;

class Tokenable extends \October\Rain\Extension\ExtensionBase
{
    use HasApiTokens;
    
    /**
     * @var \October\Rain\Database\Model Reference to the extended model.
     */
    protected $model;

    /**
     * Constructor
     * @param \October\Rain\Database\Model $model The extended model.
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->model->hasMany['tokens'] = [
            Passport::tokenModel(),
            'key' => 'user_id',
            'order' => 'created_at desc',
        ];
        $this->model->hasMany['clients'] = [
            Passport::clientModel(),
            'key' => 'user_id',
        ];
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param  string  $name
     * @param  array  $scopes
     * @return \Laravel\Passport\PersonalAccessTokenResult
     */
    public function createToken($name, array $scopes = [])
    {
        return Container::getInstance()->make(PersonalAccessTokenFactory::class)->make(
            $this->model->getKey(), $name, $scopes
        );
    }
}
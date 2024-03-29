<?php namespace Octobro\OAuth2\Transformers;

use RainLab\User\Models\User;
use Octobro\API\Classes\Transformer;

class UserTransformer extends Transformer
{
    public $availableIncludes = [
        'groups',
    ];

    public function data(User $user)
    {
        return [
            'id'         => (int) $user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'last_login' => filled($user->last_login) ? date($user->last_login) : null,
            'avatar'     => $this->image($user->avatar),
            'created_at' => date($user->created_at),
        ];
    }

    public function includeGroups(User $user)
    {
        return $this->collection($user->groups, new UserGroupTransformer);
    }

}
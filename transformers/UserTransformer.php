<?php namespace Sv\OAuth2\Transformers;

use Initbiz\CumulusCore\Models\Cluster;
use RainLab\User\Models\User;
use Sv\API\Classes\Transformer;

class UserTransformer extends Transformer
{
    public $availableIncludes = [
        'groups',
        'clusters',
    ];

    public function data(User $user)
    {
        return [
            'id'         => (int)$user->id,
            'name'       => $user->name,
            'username'   => $user->username,
            'email'      => $user->email,
            'last_login' => date($user->last_login),
            'avatar'     => $this->image($user->avatar),
            'created_at' => date($user->created_at),
        ];
    }

    public function includeGroups(User $user)
    {
        return $this->collection($user->groups, new UserGroupTransformer);
    }

    public function includeClusters(User $user)
    {
        return $this->collection($user->clusters()->get(), new ClusterTransformer);
    }
}

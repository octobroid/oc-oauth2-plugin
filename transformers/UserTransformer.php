<?php namespace Sv\OAuth2\Transformers;

use RainLab\User\Models\User;
use Sv\API\Classes\Transformer;
use Initbiz\CumulusCore\Models\Cluster;

class UserTransformer extends Transformer
{
    public $availableIncludes = [
        'groups',
        'clusters',
        'notifications',
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

    public function includeNotifications(User $user)
    {
        return $this->collection($user->notifications, new NotificationTransformer);
    }
}

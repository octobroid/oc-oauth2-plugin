<?php namespace Sv\OAuth2\Transformers;

use Backend\Models\User;
use Sv\API\Classes\Transformer;

class AdminTransformer extends Transformer
{
    public $availableIncludes = [
        'cluster',
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

    public function includeCluster(User $user)
    {
        return $this->item($user->cluster, new ClusterTransformer);
    }

}

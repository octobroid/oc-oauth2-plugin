<?php namespace Sv\OAuth2\Transformers;

//use RainLab\User\Models\User;
use Initbiz\CumulusCore\Models\Cluster;
use Sv\API\Classes\Transformer;

class ClusterTransformer extends Transformer
{
    public $availableIncludes = [
        //'groups',
    ];

    public function data(Cluster $cluster)
    {
        return [
            'id'         => (int)$cluster->id,
            'name'       => $cluster->name,
            'slug'       => $cluster->slug,
            'email'      => $cluster->email,
            'city'       => $cluster->city,
            'phone'      => $cluster->phone,
            //'avatar'     => $this->image($cluster->avatar),
            'created_at' => date($cluster->created_at),
        ];
    }

//    public function includeGroups(User $cluster)
//    {
//        return $this->collection($cluster->groups, new UserGroupTransformer);
//    }

}

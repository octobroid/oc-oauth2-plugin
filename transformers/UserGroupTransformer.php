<?php namespace Sv\OAuth2\Transformers;

use RainLab\User\Models\UserGroup;
use Sv\API\Classes\Transformer;

class UserGroupTransformer extends Transformer
{
    public function data(UserGroup $userGroup)
    {
        return [
            'id'          => (int) $userGroup->id,
            'name'        => $userGroup->name,
            'code'        => $userGroup->code,
            'description' => $userGroup->description,
        ];
    }
}

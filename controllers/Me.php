<?php namespace Sv\OAuth2\Controllers;

use Event;
use Sv\API\Classes\Base64;
use Sv\API\Classes\ApiController;
use Sv\OAuth2\Transformers\UserTransformer;

/**
 * @group OAUTH2
 * @authenticated
 */
class Me extends ApiController
{
    public function show()
    {
        $user = $this->getUser();

        /**
         * Extensibility
         */
        Event::fire('sv.oauth2.beforeShow', [$user, $this->data]);

        return $this->respondWithItem($user, new UserTransformer);
    }

    public function update()
    {
        $user = $this->getUser();

        /**
         * Extensibility
         */
        Event::fire('sv.oauth2.beforeUpdate', [$user, $this->data]);

        $user->fill($this->data);

        if ($this->input->has('avatar') && data_get($this->data, 'avatar') != null) {
            $user->avatar = Base64::base64ToFile($this->data['avatar']);
        } else if (empty(data_get($this->data, 'avatar')) && $user->avatar) {
            $user->avatar->delete();
            $user->avatar = null;
        }

        $user->save();

        /**
         * Extensibility
         */
        Event::fire('sv.oauth2.update', [$user, $this->data]);

        return $this->respondWithItem($user, new UserTransformer);
    }

}

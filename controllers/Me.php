<?php namespace Octobro\OAuth2\Controllers;

use Event;
use Octobro\API\Classes\Base64;
use Octobro\API\Classes\ApiController;
use Octobro\OAuth2\Transformers\UserTransformer;

class Me extends ApiController
{
    public function show()
    {
        $user = $this->getUser();

        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.beforeShow', [$user, $this->data]);
        
        return $this->respondWithItem($user, new UserTransformer);
    }

    public function update()
    {
        $user = $this->getUser();

        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.beforeUpdate', [$user, $this->data]);

        $user->fill($this->data);

        if ($this->input->has('avatar') && data_get($this->data, 'avatar') != null) {
            $user->avatar = Base64::base64ToFile($this->data['avatar']);
        } 
        // else if (empty(data_get($this->data, 'avatar')) && $user->avatar) {
        //     $user->avatar->delete();
        //     $user->avatar = null;
        // }

        $user->save();

        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.update', [$user, $this->data]);

        return $this->respondWithItem($user, new UserTransformer);
    }

    public function destroy()
    {
        $user = $this->getUser();

        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.beforeDelete', [$user]);

        $user->clearPersistCode();
        $user->delete();

        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.delete', [$user]);

        return;
    }

    public function destroyAvatar()
    {
        $user = $this->getUser();

        if ($user->avatar) {
            $user->avatar->delete();
            $user->avatar = null;
            $user->save();
        }

        return;
    }
}

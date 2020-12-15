<?php namespace Octobro\OAuth2\Controllers;

use Event;
use Octobro\API\Classes\Base64;
use Octobro\API\Classes\ApiController;
use Octobro\OAuth2\Transformers\UserTransformer;

class Me extends ApiController
{
    public function show()
    {
        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.beforeShow', [$this->getUser(), $this->data]);
        
        return $this->respondWithItem($this->getUser(), new UserTransformer);
    }

    public function update()
    {
        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.beforeUpdate', [$this->getUser(), $this->data]);

        $this->getUser()->fill($this->data);

        if ($this->input->has('avatar')) {
            $this->getUser()->avatar = Base64::base64ToFile($this->data['avatar']);
        }

        $this->getUser()->save();

        /**
         * Extensibility
         */
        Event::fire('octobro.oauth2.update', [$this->getUser(), $this->data]);

        return $this->respondWithItem($this->getUser(), new UserTransformer);
    }

}

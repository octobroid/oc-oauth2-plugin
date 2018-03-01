<?php namespace Octobro\OAuth2\Controllers;

use Octobro\API\Classes\ApiController;
use Octobro\OAuth2\Transformers\UserTransformer;

class Me extends ApiController
{
    public function show()
    {
        return $this->respondWithItem($this->getUser(), new UserTransformer);
    }

    public function update()
    {
        $this->getUser()->fill($this->data);

        if($this->input->has('avatar')) {
            $this->getUser()->avatar = $this->base64ToFile($this->data['avatar']);
        }

        $this->getUser()->save();

        return $this->respondWithItem($this->getUser(), new UserTransformer);
    }

}

<?php namespace Sv\OAuth2\Transformers;

use RainLab\Notify\Models\Notification;
use Sv\API\Classes\Transformer;

class NotificationTransformer extends Transformer
{
    public function data(Notification $item)
    {
        // todo-me, come back later...
        return $item->toArray();
    }
}

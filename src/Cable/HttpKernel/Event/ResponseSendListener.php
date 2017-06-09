<?php

namespace Cable\Kernel\Http\Event;


/**
 * Class ResponseSendListener
 * @package Cable\Kernel\Event
 */
class ResponseSendListener
{

    /**
     * send the response
     *
     * @param ResponseEvent $event
     *
     *
     */
    public function onResponse(ResponseEvent $event){
        $event->getResponse()->send();
    }
}

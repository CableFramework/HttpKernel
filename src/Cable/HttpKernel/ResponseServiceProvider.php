<?php

namespace Cable\Kernel\Http;


use Cable\Container\ServiceProvider;
use Cable\Kernel\Http\Event\ResponseSendListener;
use Cable\Kernel\Http\Event\ResponseEvent;

class ResponseServiceProvider extends ServiceProvider
{

    /**
     * register new providers or something
     *
     * @return mixed
     */
    public function boot()
    {
    }

    /**
     * register the content
     *
     * @return mixed
     */
    public function register()
    {
        $this->getContainer()
            ->make('event_dispatcher')
            ->addListener(
                ResponseEvent::NAME,
                array(new ResponseSendListener(), 'onResponse')
            );
    }
}

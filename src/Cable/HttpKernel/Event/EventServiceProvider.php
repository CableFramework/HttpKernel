<?php

namespace Cable\Kernel\Http\Event;


use Cable\Container\ServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventServiceProvider extends ServiceProvider
{

    /**
     * register new providers or something
     *
     * @return mixed
     */
    public function boot()
    {
        // TODO: Implement boot() method.
    }

    /**
     * register the content
     *
     * @return mixed
     */
    public function register()
    {
        $this->getContainer()
            ->singleton(
                'event_dispatcher',
                new EventDispatcher()
            );

        $this->getContainer()->alias('event', 'event_dispatcher');
    }
}
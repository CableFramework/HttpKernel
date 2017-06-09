<?php

namespace Cable\Kernel\Http;


use Cable\Container\Container;

class ControllerDispatcher
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $handled;
    /**
     * ControllerDispatcher constructor.
     * @param Container $container
     * @param array $handled
     */
    public function __construct(Container $container, array  $handled = [])
    {
        $this->container = $container;

        $this->handled = $handled;
    }

    /**
     * handles the route
     *
     * @return mixed
     */
    public function handle(){

        $name = $this->handled['_route'];

        // we dont need that route parameter any more
        unset($this->handled['_route']);

        list($namespace, $ctrl, $method) = $this->getControllerAndMethod($name);

        $end = substr($name, -1);

        if ($end !== '\\') {
            $namespace .= '\\';
        }

        $controller = $this->container->make($namespace . $ctrl);

        return $this->container->call($controller, $method, $this->handled);
    }

    /**
     * returns the namespace, controller and method
     *
     * @param $name
     * @return array
     * @throws RouteHandlerException
     */
    private function getControllerAndMethod($name)
    {
        if (!isset($this->handled['options'])) {
            throw new RouteHandlerException(
                sprintf(
                    '%s route did not handled as it supposed to be', $name
                )
            );
        }

        $options = $this->handled['options'];

        // we don't need that anymore
        unset($this->handled['options']);

        return array(
            $options->getNamespace(),
            $options->getController(),
            $options->getMethod()
        );
    }
}

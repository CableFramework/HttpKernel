<?php

namespace Cable\Kernel\Http;


use Cable\Config\Config;
use Cable\Container\ArgumentException;
use Cable\Container\Container;
use Cable\Container\ExpectationException;
use Cable\Container\NotFoundException;
use Cable\Kernel\Http\Event\ResponseEvent;
use Cable\Routing\Matcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class Kernel
{
    /**
     * @var Pipe
     */
    private $pipeline;


    /**
     * @var array
     */
    private $defaultMiddlewares  = [];

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $middleware;

    /**
     * the middleware cache
     *
     * @var array
     */
    private $cache;

    /**
     * Kernel constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->pipeline = $container->make(Pipe::class);
    }


    /**
     * handles the given request
     *
     * @param Request $request
     */
    public function handle(Request $request){

        $matcher = $this->container->make(Matcher::class);

        /**
         * @var Matcher $matcher
         *
         */

        $handled = $matcher->match();


        $this->dispatchMiddleware($handled);


        $this->pipeline->send($request)
            ->through($this->middleware)
            ->then($this->dispatchController($handled));
    }


    /**
     * @throws ExpectationException
     * @throws \ReflectionException
     * @throws ArgumentException
     * @throws NotFoundException
     * @throws \InvalidArgumentException
     *
     * @param array $handled
     * @return \Closure
     */
    private function dispatchController($handled)
    {
        return function (Request $request) use ($handled){
            $this->container->singleton(Request::class, $request);

            $controllerHandler = $this->container->make(ControllerDispatcher::class,
                array(
                    'container' => $this->container,
                    'handled' => $handled
                ));

            $response = $controllerHandler->handle();

            if (!$response instanceof Response) {
                $response = new Response($response);
            }

            $this->container
                ->make('event_dispatcher')
                ->dispatch(ResponseEvent::NAME, new ResponseEvent($response));
        };
    }

    /**
     * @param array $handled
     * @throws MiddlewareNotFoundException
     */
    private function dispatchMiddleware($handled)
    {
        $this->middleware = $this->defaultMiddlewares;

        if (!isset($handled['middleware'])) {
            return ;
        }

        if (null === $this->cache) {
            $this->cache = $this->container[Config::class]
                ->get('route.http.middleware', []);
        }

        $middlewares = $handled['middleware'];

        foreach($middlewares as $middleware){

           $middleware =  $this->determineAndGetMiddleware($middleware);

            if (!is_array($middleware)) {
                $middleware = [$middleware];
            }

            $this->middleware = array_merge(
                $this->middleware, $middleware
            );
        }
    }

    /**
     * @param $middleware
     * @return mixed
     * @throws MiddlewareNotFoundException
     */
    private function determineAndGetMiddleware($middleware){
        if (!isset($this->cache[$middleware])) {
            throw new MiddlewareNotFoundException(
                sprintf(
                    '%s middleware not found',
                    $middleware
                )
            );
        }

        return $this->cache[$middleware];
    }

    /**
     * @return Pipe
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * @param Pipe $pipeline
     * @return Kernel
     */
    public function setPipeline($pipeline)
    {
        $this->pipeline = $pipeline;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultMiddlewares()
    {
        return $this->defaultMiddlewares;
    }

    /**
     * @param array $defaultMiddlewares
     * @return Kernel
     */
    public function setDefaultMiddlewares($defaultMiddlewares)
    {
        $this->defaultMiddlewares = $defaultMiddlewares;
        return $this;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param Container $container
     * @return Kernel
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @param array $middleware
     * @return Kernel
     */
    public function setMiddleware($middleware)
    {
        $this->middleware = $middleware;
        return $this;
    }

    /**
     * @return array
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param array $cache
     * @return Kernel
     */
    public function setCache($cache)
    {
        $this->cache = $cache;
        return $this;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: vahit
 * Date: 09.06.2017
 * Time: 16:04
 */

namespace Cable\Kernel\Http;


use Cable\Kernel\Http\Event\ResponseEvent;
use Closure;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use Cable\Pipeline\Pipeline as BasePipeline;

/**
 * This extended pipeline catches any exceptions that occur during each slice.
 *
 * The exceptions are converted to HTTP responses for proper middleware handling.
 */
class Pipe extends BasePipeline
{
    /**
     * Get the final piece of the Closure onion.
     *
     * @throws Exception
     * @param  \Closure $destination
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination)
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Exception $e) {
                return $this->handleException($passable, $e);
            } catch (Throwable $e) {
                return $this->handleException($passable, new FatalThrowableError($e));
            }
        };
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry()
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    $slice = parent::carry();

                    $callable = $slice($stack, $pipe);
                    $called = $callable($passable);

                    // if we have a response returned, just send it and stop the progress
                    if ($called instanceof Response) {
                        $this->container
                            ->make('event_dispatcher')
                            ->dispatch(ResponseEvent::NAME, new ResponseEvent($called));

                        return;
                    }

                    // if false returns just throw an exception
                    if ($called === false) {
                        throw new MiddlewareException(
                            'Middleware has failed'
                        );
                    }

                    return $called;
                } catch (Exception $e) {
                    return $this->handleException($passable, $e);
                } catch (Throwable $e) {
                    return $this->handleException($passable, new FatalThrowableError($e));
                }
            };
        };
    }

    /**
     * Handle the given exception.
     *
     * @param  mixed $passable
     * @param  \Exception $e
     * @return mixed
     *
     * @throws \Exception
     */
    protected function handleException($passable, Exception $e)
    {
        if (!$this->container->getBoundManager()
            ->has(ExceptionHandler::class) || !$passable instanceof Request) {
            throw $e;
        }
        $handler = $this->container->make(ExceptionHandler::class);
        $handler->report($e);
        $response = $handler->render($passable, $e);
        if (method_exists($response, 'withException')) {
            $response->withException($e);
        }
        return $response;
    }
}
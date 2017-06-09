<?php

namespace Cable\Kernel\Http\Event;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ResponseEvent
 * @package Cable\Kernel\Event
 */
class ResponseEvent extends Event
{
    const NAME = 'http.send_response';

    /**
     * @var Response
     */
    private $response;

    /**
     * ResponseEvent constructor.
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }


    /**
     * @return Response
     */
    public function getResponse(){
        return $this->response;
    }

}

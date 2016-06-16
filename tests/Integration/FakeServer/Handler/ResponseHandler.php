<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class ResponseHandler implements Handler
{
    private $response;

    public function __construct($response)
    {
        $this->response = $response;
    }

    public function __invoke($conn, $sid)
    {
        printf("$sid:   Write response (base64): %s.\n", base64_encode($this->response));
        fwrite($conn, $this->response);
    }
}

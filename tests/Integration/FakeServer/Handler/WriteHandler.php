<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class WriteHandler implements Handler
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function __invoke($conn, $sid)
    {
        printf("$sid:   Write data (base64): %s.\n", base64_encode($this->data));
        fwrite($conn, $this->data);
    }
}

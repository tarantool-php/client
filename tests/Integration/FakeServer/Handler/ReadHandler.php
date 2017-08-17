<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class ReadHandler implements Handler
{
    private $length;
    private $stopOnNoData;

    public function __construct($length, $stopOnNoData = true)
    {
        $this->length = $length;
        $this->stopOnNoData = $stopOnNoData;
    }

    public function __invoke($conn, $sid)
    {
        printf("$sid:   Read data ($this->length bytes).\n");
        $data = fread($conn, $this->length);

        if ($this->stopOnNoData && !$data) {
            return false;
        }
    }
}

<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class ChainHandler implements Handler
{
    private $handlers = [];

    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }

    public function __invoke($conn, $sid)
    {
        foreach ($this->handlers as $handler) {
            $handler($conn, $sid);
        }
    }
}

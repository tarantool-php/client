<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

class NoopHandler implements Handler
{
    public function __invoke($conn, $sid)
    {
        echo "$sid:   Noop\n";
    }
}

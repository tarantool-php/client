<?php

namespace Tarantool\Tests\Integration\FakeServer\Handler;

class NullHandler implements Handler
{
    public function __invoke($conn, $sid)
    {
        echo "$sid:   Noop\n";
    }
}

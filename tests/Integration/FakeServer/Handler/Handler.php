<?php

namespace Tarantool\Tests\Integration\FakeServer\Handler;

interface Handler
{
    public function __invoke($conn, $sid);
}

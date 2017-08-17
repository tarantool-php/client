<?php

namespace Tarantool\Client\Tests\Integration\FakeServer\Handler;

interface Handler
{
    /**
     * @param resource $conn
     * @param string $sid
     *
     * @return void|bool
     */
    public function __invoke($conn, $sid);
}

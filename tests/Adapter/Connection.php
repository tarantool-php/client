<?php

namespace Tarantool\Tests\Adapter;

use Tarantool\Connection\Connection as BaseConnection;

class Connection implements BaseConnection
{
    private $tarantool;

    public function __construct(\Tarantool $taranool)
    {
        $this->tarantool = $taranool;
    }

    public function open()
    {
        $this->tarantool->connect();
    }

    public function close()
    {
        $this->tarantool->close();
    }

    public function isClosed()
    {
        throw new \RuntimeException(sprintf('"%s" is not supported.', __METHOD__));
    }

    public function send($data)
    {
        throw new \RuntimeException(sprintf('"%s" is not supported.', __METHOD__));
    }
}

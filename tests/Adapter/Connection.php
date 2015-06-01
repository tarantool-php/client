<?php

namespace Tarantool\Tests\Adapter;

class Connection
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
}

<?php

namespace Tarantool\Tests\Adapter;

use Tarantool\Response;

class Tarantool
{
    private $tarantool;

    public function __construct($host, $port)
    {
        $this->tarantool = new \Tarantool($host, $port);
    }

    public function disconnect()
    {
        return $this->tarantool->close();
    }

    public function getSpace($space)
    {
        return new Space($this->tarantool, $space);
    }

    public function flushSpaces()
    {
        return $this->tarantool->flushSchema();
    }

    public function __call($method, array $args)
    {
        try {
            $result = call_user_func_array([$this->tarantool, $method], $args);
        } catch (\Exception $e) {
            throw ExceptionFactory::create($e);
        }

        return new Response(0, $result);
    }
}

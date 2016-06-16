<?php

namespace Tarantool\Client\Tests\Adapter;

use Tarantool\Client\Response;

class Tarantool
{
    private $tarantool;

    public function __construct($host, $port)
    {
        try {
            $this->tarantool = new \Tarantool($host, $port);
        } catch (\Exception $e) {
            throw ExceptionFactory::create($e);
        }
    }

    public function getConnection()
    {
        return new Connection($this->tarantool);
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

        if (is_bool($result)) {
            $result = [$result];
        }

        return new Response(0, $result);
    }
}

<?php

namespace Tarantool\Request;

class PingRequest extends Request
{
    public function getType()
    {
        return self::TYPE_PING;
    }

    public function getBody()
    {
    }
}

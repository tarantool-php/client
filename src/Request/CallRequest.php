<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class CallRequest implements Request
{
    private $funcName;
    private $args;

    public function __construct($funcName, array $args = [])
    {
        $this->funcName = $funcName;
        $this->args = $args;
    }

    public function getType()
    {
        return self::TYPE_CALL;
    }

    public function getBody()
    {
        return [
            IProto::FUNCTION_NAME => $this->funcName,
            IProto::TUPLE => $this->args,
        ];
    }
}

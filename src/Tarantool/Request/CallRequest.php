<?php

namespace Tarantool\Request;

use Tarantool\IProto;

class CallRequest extends Request
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

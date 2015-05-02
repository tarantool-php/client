<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

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
            Iproto::FUNCTION_NAME => $this->funcName,
            Iproto::TUPLE => $this->args,
        ];
    }
}

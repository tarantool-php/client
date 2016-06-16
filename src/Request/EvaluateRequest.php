<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class EvaluateRequest implements Request
{
    private $expr;
    private $args;

    public function __construct($expr, array $args = [])
    {
        $this->expr = $expr;
        $this->args = $args;
    }

    public function getType()
    {
        return self::TYPE_EVALUATE;
    }

    public function getBody()
    {
        return [
            IProto::EXPR => $this->expr,
            IProto::TUPLE => $this->args,
        ];
    }
}

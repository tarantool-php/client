<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

class EvaluateRequest extends Request
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
            Iproto::EXPR => $this->expr,
            Iproto::TUPLE => $this->args,
        ];
    }
}

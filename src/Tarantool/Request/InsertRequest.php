<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

class InsertRequest extends Request
{
    private $spaceNo;
    private $values;

    public function __construct($spaceNo, array $values)
    {
        $this->spaceNo = $spaceNo;
        $this->values = $values;
    }

    public function getType()
    {
        return self::TYPE_INSERT;
    }

    public function getBody()
    {
        return [
            Iproto::SPACE_ID => $this->spaceNo,
            Iproto::TUPLE => $this->values,
        ];
    }
}

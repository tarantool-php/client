<?php

namespace Tarantool\Request;

use Tarantool\IProto;

class ReplaceRequest extends Request
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
        return self::TYPE_REPLACE;
    }

    public function getBody()
    {
        return [
            IProto::SPACE_ID => $this->spaceNo,
            IProto::TUPLE => $this->values,
        ];
    }
}

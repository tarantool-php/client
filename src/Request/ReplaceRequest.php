<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class ReplaceRequest implements Request
{
    private $spaceId;
    private $values;

    public function __construct($spaceId, array $values)
    {
        $this->spaceId = $spaceId;
        $this->values = $values;
    }

    public function getType()
    {
        return self::TYPE_REPLACE;
    }

    public function getBody()
    {
        return [
            IProto::SPACE_ID => $this->spaceId,
            IProto::TUPLE => $this->values,
        ];
    }
}

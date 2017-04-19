<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class UpsertRequest implements Request
{
    private $spaceId;
    private $values;
    private $operations;

    public function __construct($spaceId, array $values, array $operations)
    {
        $this->spaceId = $spaceId;
        $this->values = $values;
        $this->operations = $operations;
    }

    public function getType()
    {
        return self::TYPE_UPSERT;
    }

    public function getBody()
    {
        return [
            IProto::SPACE_ID => $this->spaceId,
            IProto::TUPLE => $this->values,
            IProto::OPERATIONS => $this->operations,
        ];
    }
}

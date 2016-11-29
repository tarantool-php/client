<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class UpdateRequest implements Request
{
    private $spaceId;
    private $indexId;
    private $key;
    private $operations;

    public function __construct($spaceId, $indexId, $key, array $operations)
    {
        $this->spaceId = $spaceId;
        $this->indexId = $indexId;
        $this->key = (array) $key;
        $this->operations = $operations;
    }

    public function getType()
    {
        return self::TYPE_UPDATE;
    }

    public function getBody()
    {
        return [
            IProto::SPACE_ID => $this->spaceId,
            IProto::INDEX_ID => $this->indexId,
            IProto::KEY => $this->key,
            IProto::TUPLE => $this->operations,
        ];
    }
}

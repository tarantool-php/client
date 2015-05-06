<?php

namespace Tarantool\Request;

use Tarantool\IProto;

class UpdateRequest extends Request
{
    private $spaceNo;
    private $indexNo;
    private $key;
    private $operations;

    public function __construct($spaceNo, $indexNo, $key, array $operations)
    {
        $this->spaceNo = $spaceNo;
        $this->indexNo = $indexNo;
        $this->key = $key;
        $this->operations = $operations;
    }

    public function getType()
    {
        return self::TYPE_UPDATE;
    }

    public function getBody()
    {
        return [
            IProto::SPACE_ID => $this->spaceNo,
            IProto::INDEX_ID => $this->indexNo,
            IProto::KEY => [$this->key],
            IProto::TUPLE => $this->operations,
        ];
    }
}

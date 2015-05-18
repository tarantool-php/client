<?php

namespace Tarantool\Request;

use Tarantool\IProto;

class SelectRequest implements Request
{
    private $spaceNo;
    private $indexNo;
    private $key;
    private $offset;
    private $limit;
    private $iterator;

    public function __construct($spaceNo, $indexNo, array $key, $offset, $limit, $iterator)
    {
        $this->spaceNo = $spaceNo;
        $this->indexNo = $indexNo;
        $this->key = $key;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->iterator = $iterator;
    }

    public function getType()
    {
        return self::TYPE_SELECT;
    }

    public function getBody()
    {
        return [
            IProto::KEY => $this->key,
            IProto::SPACE_ID => $this->spaceNo,
            IProto::INDEX_ID => $this->indexNo,
            IProto::LIMIT => $this->limit,
            IProto::OFFSET => $this->offset,
            IProto::ITERATOR => $this->iterator,
        ];
    }
}

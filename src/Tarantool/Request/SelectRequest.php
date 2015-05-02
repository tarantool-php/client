<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

class SelectRequest extends Request
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
            Iproto::KEY => $this->key,
            Iproto::SPACE_ID => $this->spaceNo,
            Iproto::INDEX_ID => $this->indexNo,
            Iproto::LIMIT => $this->limit,
            Iproto::OFFSET => $this->offset,
            Iproto::ITERATOR => $this->iterator,
        ];
    }
}

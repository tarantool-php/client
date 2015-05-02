<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

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
            Iproto::SPACE_ID => $this->spaceNo,
            Iproto::INDEX_ID => $this->indexNo,
            Iproto::KEY => [$this->key],
            Iproto::TUPLE => $this->operations,
        ];
    }
}

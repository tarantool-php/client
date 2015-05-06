<?php

namespace Tarantool\Request;

use Tarantool\IProto;

class DeleteRequest extends Request
{
    private $spaceNo;
    private $indexNo;
    private $key;

    public function __construct($spaceNo, $indexNo, array $key)
    {
        $this->spaceNo = $spaceNo;
        $this->indexNo = $indexNo;
        $this->key = $key;
    }

    public function getType()
    {
        return self::TYPE_DELETE;
    }

    public function getBody()
    {
        return [
            IProto::SPACE_ID => $this->spaceNo,
            IProto::INDEX_ID => $this->indexNo,
            IProto::KEY => $this->key,
        ];
    }
}

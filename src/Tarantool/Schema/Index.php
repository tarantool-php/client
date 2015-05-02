<?php

namespace Tarantool\Schema;

class Index
{
    const SPACE_PRIMARY = 0;
    const SPACE_NAME = 2;
    const INDEX_PRIMARY = 0;
    const INDEX_NAME = 2;

    private $id;
    private $name;
    private $index;
    private $unique;

    public function __construct(array $data)
    {
        $this->id = $data[1];
        $this->name = $data[2];
        $this->index = $data[3];
        $this->unique = $data[4];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
}

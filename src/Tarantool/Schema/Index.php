<?php

namespace Tarantool\Schema;

class Index
{
    const SPACE_PRIMARY = 0;
    const SPACE_NAME = 2;
    const INDEX_PRIMARY = 0;
    const INDEX_NAME = 2;

    private $space;
    private $id;
    private $name;

    public function __construct(Space $space, $id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->space = $space;
        $this->space->addIndex($this);
    }

    public function getSpace()
    {
        return $this->space;
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

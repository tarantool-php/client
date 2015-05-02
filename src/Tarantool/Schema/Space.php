<?php

namespace Tarantool\Schema;

class Space
{
    const SCHEMA = 272;
    const SPACE = 280;
    const INDEX = 288;
    const FUNC = 296;
    const USER = 304;
    const PRIV = 312;
    const CLUSTER = 320;

    private $id;
    private $arity;
    private $name;
    private $indexes = [];

    public function __construct(array $data)
    {
        $this->id = $data[0];
        $this->arity = $data[1];
        $this->name = $data[2];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function hasIndex($index)
    {
        return isset($this->indexes[$index]);
    }

    public function getIndex($index)
    {
        return $this->indexes[$index];
    }

    public function addIndex(Index $index)
    {
        $this->indexes[$index->getId()] = $index;

        if ($name = $index->getName()) {
            $this->indexes[$name] = $index;
        }
    }
}

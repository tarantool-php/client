<?php

namespace Tarantool\Schema;

use Tarantool\Exception\Exception;

class Space
{
    const SCHEMA = 272;
    const SPACE = 280;
    const INDEX = 288;
    const FUNC = 296;
    const USER = 304;
    const PRIV = 312;
    const CLUSTER = 320;

    private $schema;
    private $id;
    private $name;

    /**
     * @var Index[]
     */
    private $indexes = [];

    public function __construct(Schema $schema, $id, $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->schema = $schema;
        $this->schema->addSpace($this);
    }

    public function getSchema()
    {
        return $this->schema;
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

    public function addIndex(Index $index)
    {
        $this->indexes[$index->getId()] = $index;

        if ($name = $index->getName()) {
            $this->indexes[$name] = $index;
        }
    }

    public function getIndex($index)
    {
        if (isset($this->indexes[$index])) {
            return $this->indexes[$index];
        }

        $client = $this->schema->getClient();

        $_index = is_string($index) ? Index::INDEX_NAME : Index::INDEX_PRIMARY;
        $response = $client->select(Space::INDEX, [$this->id, $index], $_index);
        $data = $response->getData();

        if (empty($data)) {
            throw new Exception(sprintf('There\'s no index with %s "%s" in space "%s".',
                is_string($index) ? 'name' : 'id',
                $index,
                $this->name ?: $this->id
            ));
        }

        return new Index($this, $data[0][1], $data[0][2]);
    }

    public function getIndexes()
    {
        return $this->indexes;
    }

    public function flush()
    {
        $this->indexes = [];
    }
}

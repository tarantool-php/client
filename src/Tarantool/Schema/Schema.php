<?php

namespace Tarantool\Schema;

use Tarantool\Client;
use Tarantool\Exception\Exception;

class Schema
{
    private $client;
    private $spaces = [];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getSpace($space)
    {
        if (isset($this->spaces[$space])) {
            return $this->spaces[$space];
        }

        $index = is_string($space) ? Index::SPACE_NAME : Index::SPACE_PRIMARY;
        $response = $this->client->select(Space::SPACE, [$space], $index);
        $data = $response->getData();

        if (empty($data)) {
            throw new Exception("Space '$space' does not exist");
        }

        $space = new Space($data[0]);
        $this->addSpace($space);

        return $space;
    }

    public function getIndex($space, $index)
    {
        $space = $this->getSpace($space);

        if ($space->hasIndex($index)) {
            return $space->getIndex($index);
        }

        $_index = is_string($index) ? Index::INDEX_NAME : Index::INDEX_PRIMARY;
        $response = $this->client->select(Space::INDEX, [$space->getId(), $index], $_index);
        $data = $response->getData();

        if (empty($data)) {
            throw new Exception(sprintf('There\'s no index with %s "%s" in space "%s".',
                is_string($index) ? 'name' : 'id',
                $index,
                $space->getName()
            ));
        }

        $index = new Index($data[0]);
        $space->addIndex($index);

        return $index;
    }

    public function addSpace(Space $space)
    {
        $this->spaces[$space->getId()] = $space;

        if ($name = $space->getName()) {
            $this->spaces[$name] = $space;
        }
    }

    public function flush()
    {
        $this->spaces = [];
    }
}

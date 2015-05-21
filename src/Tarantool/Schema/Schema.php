<?php

namespace Tarantool\Schema;

use Tarantool\Client;
use Tarantool\Exception\Exception;

class Schema
{
    private $client;

    /**
     * @var Space[]
     */
    private $spaces = [];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function hasSpace($space)
    {
        return isset($this->spaces[$space]);
    }

    public function addSpace(Space $space)
    {
        if (isset($this->spaces[$space->getId()])) {
            $cachedSpace = $this->spaces[$space->getId()];
            foreach ($cachedSpace->getIndexes() as $index) {
                $space->addIndex($index);
            }
        }

        $this->spaces[$space->getId()] = $space;

        if ($name = $space->getName()) {
            $this->spaces[$name] = $space;
        }
    }

    public function getSpace($space)
    {
        if (isset($this->spaces[$space])) {
            return $this->spaces[$space];
        }

        if (!is_string($space)) {
            return new Space($this, $space, null);
        }

        $index = is_string($space) ? Index::SPACE_NAME : Index::SPACE_PRIMARY;
        $response = $this->client->select(Space::SPACE, [$space], $index);
        $data = $response->getData();

        if (empty($data)) {
            throw new Exception("Space '$space' does not exist");
        }

        return new Space($this, $data[0][0], $data[0][2]);
    }

    public function getSpaces()
    {
        return $this->spaces;
    }

    public function flush()
    {
        $this->spaces = [];
    }
}

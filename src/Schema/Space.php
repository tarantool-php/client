<?php

namespace Tarantool\Client\Schema;

use Tarantool\Client\Client;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Request\DeleteRequest;
use Tarantool\Client\Request\InsertRequest;
use Tarantool\Client\Request\ReplaceRequest;
use Tarantool\Client\Request\SelectRequest;
use Tarantool\Client\Request\UpdateRequest;
use Tarantool\Client\Request\UpsertRequest;

class Space
{
    const VSPACE = 281;
    const VINDEX = 289;

    private $client;
    private $id;
    private $indexes = [];

    public function __construct(Client $client, $id)
    {
        $this->client = $client;
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function select(array $key = null, $index = null, $limit = null, $offset = null, $iteratorType = null)
    {
        $key = null === $key ? [] : $key;
        $offset = null === $offset ? 0 : $offset;
        $limit = null === $limit ? PHP_INT_MAX & 0xffffffff : $limit;
        $iteratorType = null === $iteratorType ? 0 : $iteratorType;
        $index = $this->normalizeIndex($index);

        $request = new SelectRequest($this->id, $index, $key, $offset, $limit, $iteratorType);

        return $this->client->sendRequest($request);
    }

    public function insert(array $values)
    {
        $request = new InsertRequest($this->id, $values);

        return $this->client->sendRequest($request);
    }

    public function replace(array $values)
    {
        $request = new ReplaceRequest($this->id, $values);

        return $this->client->sendRequest($request);
    }

    public function update($key, array $operations, $index = null)
    {
        $index = $this->normalizeIndex($index);
        $request = new UpdateRequest($this->id, $index, $key, $operations);

        return $this->client->sendRequest($request);
    }

    public function upsert(array $values, array $operations)
    {
        $request = new UpsertRequest($this->id, $values, $operations);

        return $this->client->sendRequest($request);
    }

    public function delete(array $key, $index = null)
    {
        $index = $this->normalizeIndex($index);
        $request = new DeleteRequest($this->id, $index, $key);

        return $this->client->sendRequest($request);
    }

    public function flushIndexes()
    {
        $this->indexes = [];
    }

    private function getIndexIdByName($indexName)
    {
        if (isset($this->indexes[$indexName])) {
            return $this->indexes[$indexName];
        }

        $schema = $this->client->getSpace(Space::VINDEX);
        $response = $schema->select([$this->id, $indexName], Index::INDEX_NAME);
        $data = $response->getData();

        if (empty($data)) {
            throw new Exception("No index '$indexName' is defined in space #{$this->id}");
        }

        return $this->indexes[$indexName] = $response->getData()[0][1];
    }

    private function normalizeIndex($index)
    {
        if (null === $index) {
            return 0;
        }

        if (is_int($index)) {
            return $index;
        }

        return $this->getIndexIdByName($index);
    }
}

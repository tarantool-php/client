<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Schema;

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Keys;
use Tarantool\Client\Request\DeleteRequest;
use Tarantool\Client\Request\InsertRequest;
use Tarantool\Client\Request\ReplaceRequest;
use Tarantool\Client\Request\SelectRequest;
use Tarantool\Client\Request\UpdateRequest;
use Tarantool\Client\Request\UpsertRequest;

final class Space
{
    public const VSPACE_ID = 281;
    public const VINDEX_ID = 289;

    private $handler;
    private $id;
    private $indexes = [];

    public function __construct(Handler $handler, int $id)
    {
        $this->handler = $handler;
        $this->id = $id;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function select(Criteria $criteria) : array
    {
        $index = $criteria->getIndex();

        if (\is_string($index)) {
            $index = $this->getIndexIdByName($index);
        }

        $request = new SelectRequest(
            $this->id,
            $index,
            $criteria->getKey(),
            $criteria->getOffset(),
            $criteria->getLimit(),
            $criteria->getIteratorType()
        );

        return $this->handler->handle($request)->getBodyField(Keys::DATA);
    }

    public function insert(array $tuple) : array
    {
        $request = new InsertRequest($this->id, $tuple);

        return $this->handler->handle($request)->getBodyField(Keys::DATA);
    }

    public function replace(array $tuple) : array
    {
        $request = new ReplaceRequest($this->id, $tuple);

        return $this->handler->handle($request)->getBodyField(Keys::DATA);
    }

    public function update(array $key, Operations $operations, $index = 0) : array
    {
        if (\is_string($index)) {
            $index = $this->getIndexIdByName($index);
        }

        $request = new UpdateRequest($this->id, $index, $key, $operations->toArray());

        return $this->handler->handle($request)->getBodyField(Keys::DATA);
    }

    public function upsert(array $tuple, Operations $operations) : void
    {
        $request = new UpsertRequest($this->id, $tuple, $operations->toArray());

        $this->handler->handle($request);
    }

    public function delete(array $key, $index = 0) : array
    {
        if (\is_string($index)) {
            $index = $this->getIndexIdByName($index);
        }

        $request = new DeleteRequest($this->id, $index, $key);

        return $this->handler->handle($request)->getBodyField(Keys::DATA);
    }

    public function flushIndexes() : void
    {
        $this->indexes = [];
    }

    private function getIndexIdByName(string $indexName) : int
    {
        if (isset($this->indexes[$indexName])) {
            return $this->indexes[$indexName];
        }

        $schema = new self($this->handler, self::VINDEX_ID);
        $data = $schema->select(Criteria::key([$this->id, $indexName])->andIndex(IndexIds::INDEX_NAME));

        if ([] === $data) {
            throw RequestFailed::unknownIndex($indexName, $this->id);
        }

        return $this->indexes[$indexName] = $data[0][1];
    }
}

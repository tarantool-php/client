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

namespace Tarantool\Client;

final class SqlQueryResult implements \IteratorAggregate, \Countable
{
    private $data;
    private $metadata;
    private $keys;

    public function __construct(array $data, array $metadata)
    {
        $this->data = $data;
        $this->metadata = $metadata;

        unset($this->keys);
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getMetadata() : array
    {
        return $this->metadata;
    }

    public function isEmpty() : bool
    {
        return [] === $this->data;
    }

    public function getFirst() : ?array
    {
        return $this->data ? \array_combine($this->keys, \reset($this->data)) : null;
    }

    public function getLast() : ?array
    {
        return $this->data ? \array_combine($this->keys, \end($this->data)) : null;
    }

    public function getIterator() : \Generator
    {
        foreach ($this->data as $item) {
            yield \array_combine($this->keys, $item);
        }
    }

    public function count() : int
    {
        return \count($this->data);
    }

    public function __get(string $property) : array
    {
        return $this->keys = $this->metadata ? \array_column($this->metadata, 0) : [];
    }
}

<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client;

final class SqlQueryResult implements \ArrayAccess, \Countable, \IteratorAggregate
{
    /** @var array<int, mixed> */
    private $data;

    /** @var array<int, array<int, string>> */
    private $metadata;

    /** @var array<int, string> */
    private $keys;

    public function __construct(array $data, array $metadata)
    {
        $this->data = $data;
        $this->metadata = $metadata;
        $this->keys = $metadata ? \array_column($metadata, 0) : [];
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
        return !$this->data;
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

    public function offsetExists($offset) : bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset) : array
    {
        if (!isset($this->data[$offset])) {
            throw new \OutOfBoundsException(\sprintf('The offset "%s" does not exist', $offset));
        }

        return \array_combine($this->keys, $this->data[$offset]);
    }

    public function offsetSet($offset, $value) : void
    {
        throw new \BadMethodCallException(self::class.' object cannot be modified');
    }

    public function offsetUnset($offset) : void
    {
        throw new \BadMethodCallException(self::class.' object cannot be modified');
    }
}

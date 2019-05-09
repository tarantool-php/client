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

final class SqlQueryResult implements \IteratorAggregate
{
    private $data;
    private $metadata;

    public function __construct(array $data, array $metadata)
    {
        $this->data = $data;
        $this->metadata = $metadata;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getMetadata() : array
    {
        return $this->metadata;
    }

    public function getIterator() : \Generator
    {
        $keys = \array_column($this->metadata, 0);

        foreach ($this->data as $item) {
            yield \array_combine($keys, $item);
        }
    }
}

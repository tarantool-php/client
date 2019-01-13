<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client;

final class SqlQueryResult
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
}

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

namespace Tarantool\Client\Response;

use Tarantool\Client\IProto;

final class SqlQueryResponse
{
    private $sync;
    private $data;
    private $metadata;

    public function __construct(int $sync, array $data, array $metadata)
    {
        $this->sync = $sync;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    public function getSync() : int
    {
        return $this->sync;
    }

    public function getData() : array
    {
        return $this->data;
    }

    public function getMetadata() : array
    {
        return $this->metadata;
    }

    public static function createFromRaw(RawResponse $response) : self
    {
        return new self(
            $response->getHeaderField(IProto::SYNC),
            $response->getBodyField(IProto::DATA),
            $response->getBodyField(IProto::METADATA)
        );
    }
}

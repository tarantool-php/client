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

final class Response
{
    public const TYPE_ERROR = 0x8000;

    private $header;
    private $body;

    public function __construct(array $header, array $body)
    {
        $this->header = $header;
        $this->body = $body;
    }

    public function isError() : bool
    {
        $code = $this->header[IProto::CODE];

        return $code >= self::TYPE_ERROR;
    }

    public function getCode() : int
    {
        return $this->header[IProto::CODE];
    }

    public function getSync() : int
    {
        return $this->header[IProto::SYNC];
    }

    public function getSchemaId() : int
    {
        return $this->header[IProto::SCHEMA_ID];
    }

    public function getBodyField(int $code)
    {
        if (!isset($this->body[$code])) {
            throw new \OutOfBoundsException(\sprintf('Invalid body code 0x%x.', $code));
        }

        return $this->body[$code];
    }

    public function tryGetBodyField(int $code, $default = null)
    {
        return $this->body[$code] ?? $default;
    }
}

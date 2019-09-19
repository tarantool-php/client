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
        $code = $this->header[Keys::CODE];

        return $code >= self::TYPE_ERROR;
    }

    public function getCode() : int
    {
        return $this->header[Keys::CODE];
    }

    public function getSync() : int
    {
        return $this->header[Keys::SYNC];
    }

    public function getSchemaId() : int
    {
        return $this->header[Keys::SCHEMA_ID];
    }

    public function getBodyField(int $key)
    {
        if (!isset($this->body[$key])) {
            throw new \OutOfRangeException(\sprintf('Invalid body key 0x%x.', $key));
        }

        return $this->body[$key];
    }

    public function tryGetBodyField(int $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }
}

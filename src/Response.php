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

use Tarantool\Client\Exception\UnexpectedResponse;

final class Response
{
    public const TYPE_ERROR = 0x8000;

    private array $header;
    private array $body;

    public function __construct(array $header, array $body)
    {
        self::validateHeaderField($header, Keys::CODE);
        self::validateHeaderField($header, Keys::SYNC);
        self::validateHeaderField($header, Keys::SCHEMA_ID);

        $this->header = $header;
        $this->body = $body;
    }

    public function isError() : bool
    {
        return $this->getCode() >= self::TYPE_ERROR;
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
        if (\array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        throw new UnexpectedResponse(\sprintf('Missing response body field 0x%x', $key));
    }

    public function tryGetBodyField(int $key, $default = null)
    {
        return \array_key_exists($key, $this->body) ? $this->body[$key] : $default;
    }

    public function hasBodyField(int $key) : bool
    {
        return \array_key_exists($key, $this->body);
    }

    private static function validateHeaderField(array $header, int $key) : void
    {
        if (!\array_key_exists($key, $header) || !\is_int($header[$key])) {
            throw new UnexpectedResponse(\sprintf('Missing or invalid response header field 0x%x', $key));
        }
    }
}

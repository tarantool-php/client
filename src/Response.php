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

    /**
     * @return mixed
     */
    public function getBodyField(int $key)
    {
        if (\array_key_exists($key, $this->body)) {
            return $this->body[$key];
        }

        throw new \OutOfRangeException(\sprintf('The body key 0x%x does not exist', $key));
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function tryGetBodyField(int $key, $default = null)
    {
        return \array_key_exists($key, $this->body) ? $this->body[$key] : $default;
    }

    public function hasBodyField(int $key) : bool
    {
        return \array_key_exists($key, $this->body);
    }
}

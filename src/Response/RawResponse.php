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

final class RawResponse
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

    public function getHeaderField(int $code)
    {
        if (!isset($this->header[$code])) {
            throw new \InvalidArgumentException(\sprintf('Invalid header code 0x%x.', $code));
        }

        return $this->header[$code];
    }

    public function getBodyField(int $code)
    {
        if (!isset($this->body[$code])) {
            throw new \InvalidArgumentException(\sprintf('Invalid body code 0x%x.', $code));
        }

        return $this->body[$code];
    }

    public function tryGetBodyField(int $code, $default = null)
    {
        return $this->body[$code] ?? $default;
    }
}

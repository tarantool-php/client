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

namespace Tarantool\Client\Packer;

final class PacketLength
{
    public const SIZE_BYTES = 5;

    private function __construct()
    {
    }

    public static function pack(int $length) : string
    {
        return \pack('CN', 0xce, $length);
    }

    public static function unpack(string $data) : int
    {
        if (!isset($data[4]) || "\xce" !== $data[0]) {
            throw new \RuntimeException('Unable to unpack packet length');
        }

        return \ord($data[1]) << 24
            | \ord($data[2]) << 16
            | \ord($data[3]) << 8
            | \ord($data[4]);
    }
}

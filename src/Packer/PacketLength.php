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
        if (false === $data = @\unpack('C/N', $data)) {
            throw new \RuntimeException('Unable to unpack packet length.');
        }

        return $data[1];
    }
}

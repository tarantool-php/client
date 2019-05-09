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

use Tarantool\Client\Exception\UnpackingFailed;

final class PackUtils
{
    private function __construct()
    {
    }

    public static function packLength(int $length) : string
    {
        return \pack('CN', 0xce, $length);
    }

    public static function unpackLength(string $data) : int
    {
        if (false === $data = @\unpack('C_/Nlength', $data)) {
            throw new UnpackingFailed('Unable to unpack length value.');
        }

        return $data['length'];
    }
}

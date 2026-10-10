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

namespace Tarantool\Client\Tests\Unit\Packer;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Packer\PacketLength;

final class PacketLengthTest extends TestCase
{
    public function testPackUnpackLength() : void
    {
        $packed = PacketLength::pack(42);

        self::assertIsString($packed);
        self::assertSame(42, PacketLength::tryUnpack($packed));
    }

    public function testTryUnpackLengthReturnsNullForMalformedData() : void
    {
        self::assertNull(PacketLength::tryUnpack('foo'));
        self::assertNull(PacketLength::tryUnpack("\x00\x00\x00\x00\x00"));
    }
}

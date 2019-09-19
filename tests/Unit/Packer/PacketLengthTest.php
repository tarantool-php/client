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

namespace Tarantool\Client\Tests\Unit\Packer;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Packer\PacketLength;

final class PacketLengthTest extends TestCase
{
    public function testPackUnpackLength() : void
    {
        $packed = PacketLength::pack(42);

        self::assertIsString($packed);
        self::assertSame(42, PacketLength::unpack($packed));
    }

    public function testUnpackLengthFromMalformedData() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to unpack packet length.');

        PacketLength::unpack('foo');
    }
}

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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Request\PingRequest;

final class PacketSyncTest extends TestCase
{
    /**
     * @dataProvider provideValidSync
     */
    public function testSameSync(int $sync) : void
    {
        $handler = $this->client->getHandler();
        $connection = $handler->getConnection();
        $packer = $handler->getPacker();

        $packet = $packer->pack(new PingRequest(), $sync);
        $connection->open();
        $packet = $connection->send($packet);

        $response = $packer->unpack($packet);

        self::assertSame($sync, $response->getSync());
    }

    public function provideValidSync() : iterable
    {
        return [
            [0],
            [128],
            [65535],
            [4294967295],
            [9223372036854775807], // PHP_INT_MAX
        ];
    }
}

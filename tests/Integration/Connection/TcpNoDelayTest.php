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

namespace Tarantool\Client\Tests\Integration\Connection;

use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class TcpNoDelayTest extends TestCase
{
    public function testTcpNoDelayEnabled() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['tcp_nodelay' => true]);

        if (!$clientBuilder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $clientBuilder->getUri()));
        }

        $client = $clientBuilder->build();
        $client->ping();

        $connection = $client->getHandler()->getConnection();
        $socket = socket_import_stream(self::getRawStream($connection));

        self::assertGreaterThan(0, socket_get_option($socket, SOL_TCP, TCP_NODELAY));
    }

    public function testTcpNoDelayDisabled() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['tcp_nodelay' => false]);

        if (!$clientBuilder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $clientBuilder->getUri()));
        }

        $client = $clientBuilder->build();
        $client->ping();

        $connection = $client->getHandler()->getConnection();
        $socket = socket_import_stream(self::getRawStream($connection));

        self::assertSame(0, socket_get_option($socket, SOL_TCP, TCP_NODELAY));
    }
}

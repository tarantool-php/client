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

namespace Tarantool\Client\Tests\Integration\Connection;

use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires extension sockets
 */
final class TcpNoDelayTest extends TestCase
{
    public function testTcpNoDelayEnabled() : void
    {
        $builder = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['tcp_nodelay' => true]);

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();
        $client->ping();

        $conn = $client->getHandler()->getConnection();
        $prop = (new \ReflectionObject($conn))->getProperty('stream');
        $prop->setAccessible(true);

        $socket = socket_import_stream($prop->getValue($conn));
        self::assertSame(1, socket_get_option($socket, SOL_TCP, TCP_NODELAY));
    }

    public function testTcpNoDelayDisabled() : void
    {
        $builder = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['tcp_nodelay' => false]);

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();
        $client->ping();

        $conn = $client->getHandler()->getConnection();
        $prop = (new \ReflectionObject($conn))->getProperty('stream');
        $prop->setAccessible(true);

        $socket = socket_import_stream($prop->getValue($conn));
        self::assertSame(0, socket_get_option($socket, SOL_TCP, TCP_NODELAY));
    }
}

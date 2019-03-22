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

use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class ConnectionTest extends TestCase
{
    /**
     * @dataProvider provideAutoConnectData
     * @doesNotPerformAssertions
     */
    public function testAutoConnect(string $methodName, array $methodArgs, ?string $space = null) : void
    {
        $object = $space ? $this->client->getSpace($space) : $this->client;
        $this->client->getHandler()->getConnection()->close();

        $object->$methodName(...$methodArgs);
    }

    public function provideAutoConnectData() : iterable
    {
        return [
            ['ping', []],
            ['call', ['box.stat']],
            ['evaluate', ['return 1']],

            ['select', [[42]], 'space_conn'],
            ['insert', [[time()]], 'space_conn'],
            ['replace', [[1, 2]], 'space_conn'],
            ['update', [[1], [['+', 1, 2]]], 'space_conn'],
            ['delete', [[1]], 'space_conn'],
        ];
    }

    public function testMultipleConnect() : void
    {
        $conn = $this->client->getHandler()->getConnection();

        self::assertTrue($conn->isClosed());

        $conn->open();
        self::assertFalse($conn->isClosed());

        $conn->open();
        self::assertFalse($conn->isClosed());
    }

    public function tesMultipleDisconnect() : void
    {
        $conn = $this->client->getHandler()->getConnection();

        $conn->open();
        self::assertFalse($conn->isClosed());

        $conn->close();
        self::assertTrue($conn->isClosed());

        $conn->close();
        self::assertTrue($conn->isClosed());
    }

    public function testRegenerateSalt() : void
    {
        $conn = $this->client->getHandler()->getConnection();

        $salt1 = $conn->open();
        $salt2 = $conn->open();

        self::assertNotSame($salt1, $salt2);
    }

    public function testConnectInvalidHost() : void
    {
        $builder = ClientBuilder::createFromEnv()
            ->setHost('invalid_host');

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();

        $this->expectException(ConnectionFailed::class);
        $client->ping();
    }

    public function testConnectInvalidPort() : void
    {
        $builder = ClientBuilder::createFromEnv()
            ->setPort(123456);

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();

        $this->expectException(ConnectionFailed::class);
        $client->ping();
    }

    public function testConnectTimedOut() : void
    {
        $connectTimeout = 2;
        $builder = ClientBuilder::createFromEnv();

        // http://stackoverflow.com/q/100841/1160901
        $builder->setHost($host = '10.255.255.1');
        $builder->setConnectionOptions(['connect_timeout' => $connectTimeout]);

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();

        $start = microtime(true);

        try {
            $client->ping();
        } catch (ConnectionFailed $e) {
            if (false !== strpos($e->getMessage(), 'No route to host')) {
                self::markTestSkipped(sprintf('Unable to route to host %s.', $host));
            }

            $time = microtime(true) - $start;
            self::assertRegExp('/Failed to connect to .+?: (Connection|Operation) timed out\./', $e->getMessage());
            self::assertGreaterThanOrEqual($connectTimeout, $time);
            self::assertLessThanOrEqual($connectTimeout + 0.1, $time);

            return;
        }

        self::fail();
    }

    public function testConnectionRetry() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        $clientBuilder->setConnectionOptions(['socket_timeout' => 1]);

        $client = $clientBuilder->build();
        $retryableClient = $clientBuilder->setOptions(['max_retries' => 1])->build();

        $retryableClient->evaluate('require("fiber").sleep(2)');

        $this->expectException(CommunicationFailed::class);
        $client->evaluate('require("fiber").sleep(2)');
    }
}

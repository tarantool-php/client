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

use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;
use Tarantool\Client\Tests\GreetingDataProvider;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\AtConnectionHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\WriteHandler;
use Tarantool\Client\Tests\Integration\TestCase;

final class ConnectionTest extends TestCase
{
    /**
     * @dataProvider provideAutoConnectData
     * @doesNotPerformAssertions
     *
     * @lua create_space('test_auto_connect'):create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
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

            ['select', [Criteria::key([42])], 'test_auto_connect'],
            ['insert', [[time()]], 'test_auto_connect'],
            ['replace', [[1, 2]], 'test_auto_connect'],
            ['update', [[1], Operations::add(1, 2)], 'test_auto_connect'],
            ['delete', [[1]], 'test_auto_connect'],
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

    public function testReturnSameGreeting() : void
    {
        $conn = $this->client->getHandler()->getConnection();

        $greeting1 = $conn->open();
        $greeting2 = $conn->open();

        self::assertSame($greeting1, $greeting2);
    }

    public function testReturnNewGreeting() : void
    {
        $conn = $this->client->getHandler()->getConnection();

        $greeting1 = $conn->open();
        $conn->close();
        $greeting2 = $conn->open();

        self::assertNotSame($greeting1, $greeting2);
    }

    public function testConnectInvalidHost() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv()
            ->setHost('invalid_host');

        if (!$clientBuilder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For tcp connections only (current: "%s")', $clientBuilder->getUri()));
        }

        $client = $clientBuilder->build();

        $this->expectException(ConnectionFailed::class);
        $client->ping();
    }

    public function testConnectInvalidPort() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv()
            ->setPort(123456);

        if (!$clientBuilder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For tcp connections only (current: "%s")', $clientBuilder->getUri()));
        }

        $client = $clientBuilder->build();

        $this->expectException(ConnectionFailed::class);
        $client->ping();
    }

    public function testConnectTimedOut() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        if (!$clientBuilder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For tcp connections only (current: "%s")', $clientBuilder->getUri()));
        }

        // @see http://stackoverflow.com/q/100841/1160901
        $host = '8.8.8.8';
        $connectTimeout = 1.125;

        $client = $clientBuilder->setConnectionOptions(['connect_timeout' => $connectTimeout])
            ->setHost($host)
            ->setPort(8008)
            ->build();

        $start = microtime(true);

        try {
            $client->ping();
        } catch (ConnectionFailed $e) {
            if (1 !== preg_match('/(Connection|Operation) timed out/', $e->getMessage())) {
                self::markTestSkipped(sprintf('Unable to trigger timeout error: %s', $e->getMessage()));
            }

            $time = microtime(true) - $start;
            self::assertGreaterThanOrEqual($connectTimeout, $time);
            self::assertLessThan($connectTimeout + 0.01, $time);

            return;
        }

        self::fail();
    }

    public function testUnexpectedResponse() : void
    {
        $client = ClientBuilder::createFromEnv()->build();
        $connection = self::triggerUnexpectedResponse($client->getHandler(), new PingRequest());

        // Tarantool will answer with the ping response
        try {
            $client->evaluate('return 42');
        } catch (UnexpectedResponse $e) {
            self::assertTrue($connection->isClosed());

            return;
        }

        self::fail(UnexpectedResponse::class.' was not thrown');
    }

    public function testOpenConnectionHandlesTheMissingGreetingCorrectly() : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();

        FakeServerBuilder::create(
            new AtConnectionHandler(1, new WriteHandler('')),
            new AtConnectionHandler(2, new WriteHandler(GreetingDataProvider::generateGreeting()))
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();
        $connection = $client->getHandler()->getConnection();

        try {
            $connection->open();
            self::fail('Connection not established');
        } catch (CommunicationFailed $e) {
            self::assertMatchesRegularExpression(
                '/Error reading greeting:.+Unable to connect/i',
                $e->getMessage()
            );
            // At this point the connection was successfully established,
            // but the greeting message was not read.
        }

        // The second call should correctly handle
        // the missing greeting from the previous call.
        $connection->open();
    }
}

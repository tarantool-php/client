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
                self::markTestSkipped(sprintf('Unable to route to host %s', $host));
            }

            $time = microtime(true) - $start;
            self::assertRegExp('/Failed to connect to .+?: (Connection|Operation) timed out/', $e->getMessage());
            self::assertGreaterThanOrEqual($connectTimeout, $time);
            self::assertLessThanOrEqual($connectTimeout + 0.1, $time);

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
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();

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
            self::assertSame('Unable to read greeting', $e->getMessage());
            // at that point the connection was successfully established,
            // but the greeting message was not read
        }

        // the second call should correctly handle
        // the missing greeting from the previous call
        $connection->open();
    }
}

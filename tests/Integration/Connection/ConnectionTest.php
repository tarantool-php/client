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

use Tarantool\Client\Exception\ConnectionException;
use Tarantool\Client\Tests\GreetingDataProvider;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\ChainHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\SocketDelayHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\WriteHandler;
use Tarantool\Client\Tests\Integration\TestCase;

final class ConnectionTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testConnect() : void
    {
        $this->client->connect();
        $this->client->ping();
    }

    /**
     * @dataProvider provideAutoConnectData
     * @doesNotPerformAssertions
     */
    public function testAutoConnect(string $methodName, array $methodArgs, string $space = null) : void
    {
        $object = $space ? $this->client->getSpace($space) : $this->client;
        $this->client->disconnect();

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

    /**
     * @doesNotPerformAssertions
     */
    public function testCreateManyConnections() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();

        for ($i = 10; $i; --$i) {
            $clientBuilder->build()->connect();
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testMultipleConnect() : void
    {
        $this->client->connect();
        $this->client->connect();
    }

    public function tesMultipleDisconnect() : void
    {
        $this->client->disconnect();
        $this->client->disconnect();
    }

    public function testConnectInvalidHost() : void
    {
        $builder = ClientBuilder::createFromEnv()
            ->setHost('invalid_host');

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();

        $this->expectException(ConnectionException::class);
        $client->connect();
    }

    public function testConnectInvalidPort() : void
    {
        $builder = ClientBuilder::createFromEnv()
            ->setPort(123456);

        if (!$builder->isTcpConnection()) {
            self::markTestSkipped(sprintf('For the tcp connections only (current: "%s")', $builder->getUri()));
        }

        $client = $builder->build();

        $this->expectException(ConnectionException::class);
        $client->connect();
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
        } catch (ConnectionException $e) {
            if (false !== strpos($e->getMessage(), 'No route to host')) {
                self::markTestSkipped(sprintf('Unable to route to host %s.', $host));
            }

            $time = microtime(true) - $start;
            self::assertRegExp('/Unable to connect to .+?: (Connection|Operation) timed out\./', $e->getMessage());
            self::assertGreaterThanOrEqual($connectTimeout, $time);
            self::assertLessThanOrEqual($connectTimeout + 0.1, $time);

            return;
        }

        $this->fail();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testConnectionRetry() : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();
        $clientBuilder->setConnectionOptions([
            'socket_timeout' => 2,
            'retries' => 1,
        ]);
        $client = $clientBuilder->build();

        FakeServerBuilder::create(
            new ChainHandler([
                new SocketDelayHandler(3, true),
                new WriteHandler(GreetingDataProvider::generateGreeting()),
            ])
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client->connect();
    }
}

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
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Packer\PackUtils;
use Tarantool\Client\Tests\GreetingDataProvider;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\ChainHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\NoopHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\ReadHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\SocketDelayHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\WriteHandler;
use Tarantool\Client\Tests\Integration\TestCase;

final class ReadTest extends TestCase
{
    public function testReadLargeResponse() : void
    {
        $data = str_repeat('x', 1024 * 1024);
        $result = $this->client->evaluate('return ...', [$data]);

        self::assertSame($data, $result->getData()[0]);
    }

    public function testReadEmptyGreeting() : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();

        FakeServerBuilder::create(new NoopHandler())
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to read greeting.');

        $client->connect();
    }

    public function testSocketReadTimedOut() : void
    {
        $socketTimeout = 2;

        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();
        $clientBuilder->setConnectionOptions(['socket_timeout' => $socketTimeout]);

        FakeServerBuilder::create(new SocketDelayHandler($socketTimeout + 2))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $start = microtime(true);

        try {
            $client->ping();
        } catch (ConnectionException $e) {
            $time = microtime(true) - $start;
            self::assertSame('Read timed out.', $e->getMessage());
            self::assertGreaterThanOrEqual($socketTimeout, $time);
            self::assertLessThanOrEqual($socketTimeout + 0.1, $time);

            return;
        }

        $this->fail();
    }

    public function testUnableToReadResponseLength() : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();

        FakeServerBuilder::create(
            new ChainHandler([
                new WriteHandler(GreetingDataProvider::generateGreeting()),
                new ReadHandler(1),
            ])
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Unable to read response length.');

        $client->ping();
    }

    public function testReadResponseLengthTimedOut() : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();
        $clientBuilder->setConnectionOptions(['socket_timeout' => 1]);

        FakeServerBuilder::create(
            new ChainHandler([
                new WriteHandler(GreetingDataProvider::generateGreeting()),
                new ReadHandler(1),
                new SocketDelayHandler(2),
            ])
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Read timed out.');

        $client->ping();
    }

    public function testUnableToReadResponse() : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();

        FakeServerBuilder::create(
            new ChainHandler([
                new WriteHandler(GreetingDataProvider::generateGreeting()),
                new ReadHandler(1),
                new WriteHandler(PackUtils::packLength(42)),
            ])
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Unable to read response.');

        $client->ping();
    }

    public function testReadResponseTimedOut() : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();
        $clientBuilder->setConnectionOptions(['socket_timeout' => 1]);

        FakeServerBuilder::create(
            new ChainHandler([
                new WriteHandler(GreetingDataProvider::generateGreeting()),
                new ReadHandler(1),
                new WriteHandler(PackUtils::packLength(42)),
                new ReadHandler(1),
                new SocketDelayHandler(2),
            ])
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Read timed out.');

        $client->ping();
    }
}

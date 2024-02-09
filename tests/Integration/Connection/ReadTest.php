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
use Tarantool\Client\Packer\PacketLength;
use Tarantool\Client\Tests\GreetingDataProvider;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\SleepHandler;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\WriteHandler;
use Tarantool\Client\Tests\Integration\TestCase;

final class ReadTest extends TestCase
{
    public function testReadLargeResponse() : void
    {
        $str = str_repeat('x', 1024 * 1024);
        $result = $this->client->evaluate('return ...', $str);

        self::assertSame([$str], $result);
    }

    public function testReadEmptyGreeting() : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();

        FakeServerBuilder::create()
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(CommunicationFailed::class);
        $this->expectExceptionMessageMatches('/Error reading greeting:.+Unable to connect/i');

        $client->ping();
    }

    public function testUnableToReadResponseLength() : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();

        FakeServerBuilder::create(
            new WriteHandler(GreetingDataProvider::generateGreeting()),
            new SleepHandler(1)
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(CommunicationFailed::class);
        $this->expectExceptionMessageMatches('/Error reading response length:.+Unable to connect/i');

        $client->ping();
    }

    public function testReadResponseLengthTimedOut() : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();
        $clientBuilder->setConnectionOptions(['socket_timeout' => 1]);

        FakeServerBuilder::create(
            new WriteHandler(GreetingDataProvider::generateGreeting()),
            new SleepHandler(2)
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(CommunicationFailed::class);
        $this->expectExceptionMessage('Read timed out');

        $client->ping();
    }

    public function testUnableToReadResponse() : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();

        FakeServerBuilder::create(
            new WriteHandler(GreetingDataProvider::generateGreeting()),
            new WriteHandler(PacketLength::pack(42)),
            new SleepHandler(1)
        )
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(CommunicationFailed::class);
        $this->expectExceptionMessageMatches('/Error reading response:.+Unable to connect/i');

        $client->ping();
    }

    public function testSocketReadTimedOut() : void
    {
        $socketTimeout = 1.125;

        $client = ClientBuilder::createFromEnv()
            ->setConnectionOptions(['socket_timeout' => $socketTimeout])
            ->build();

        $start = microtime(true);

        try {
            $client->evaluate('require("fiber").sleep(2)');
        } catch (CommunicationFailed $e) {
            $time = microtime(true) - $start;
            self::assertSame('Read timed out', $e->getMessage());
            self::assertGreaterThanOrEqual($socketTimeout, $time);
            self::assertLessThan($socketTimeout + 0.01, $time);

            return;
        }

        self::fail();
    }
}

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
use Tarantool\Client\Exception\InvalidGreeting;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\WriteHandler;
use Tarantool\Client\Tests\Integration\TestCase;

final class ParseGreetingTest extends TestCase
{
    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     */
    public function testParseGreetingWithInvalidServerName(string $greeting) : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();

        FakeServerBuilder::create(new WriteHandler($greeting))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        try {
            $client->connect();
        } catch (CommunicationFailed $e) {
            self::assertSame('Unable to read greeting.', $e->getMessage());

            return;
        } catch (InvalidGreeting $e) {
            self::assertSame('Invalid greeting: unable to recognize Tarantool server.', $e->getMessage());

            return;
        }

        $this->fail();
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     */
    public function testParseGreetingWithInvalidSalt(string $greeting) : void
    {
        $clientBuilder = ClientBuilder::createFromEnvForTheFakeServer();

        FakeServerBuilder::create(new WriteHandler($greeting))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        $this->expectException(InvalidGreeting::class);
        $this->expectExceptionMessage('Invalid greeting: unable to parse salt.');

        $client->connect();
    }
}
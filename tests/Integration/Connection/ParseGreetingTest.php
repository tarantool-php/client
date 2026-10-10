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

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tarantool\Client\Connection\Greeting;
use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Tests\GreetingDataProvider;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\FakeServerBuilder;
use Tarantool\Client\Tests\Integration\FakeServer\Handler\WriteHandler;
use Tarantool\Client\Tests\Integration\TestCase;

final class ParseGreetingTest extends TestCase
{
    #[DataProviderExternal(GreetingDataProvider::class, 'provideGreetingsWithInvalidServerName')]
    public function testParseGreetingWithInvalidServerName(string $greeting) : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();

        FakeServerBuilder::create(new WriteHandler($greeting))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->build();

        try {
            $client->ping();
        } catch (CommunicationFailed $e) {
            self::assertMatchesRegularExpression(
                '/Error reading greeting:.+Unable to connect/i',
                $e->getMessage()
            );

            return;
        } catch (\RuntimeException $e) {
            self::assertSame('Unable to recognize Tarantool server', $e->getMessage());

            return;
        }

        self::fail();
    }

    #[DataProviderExternal(GreetingDataProvider::class, 'provideGreetingsWithInvalidSalt')]
    public function testParseGreetingWithInvalidSalt(string $greeting) : void
    {
        $clientBuilder = ClientBuilder::createForFakeServer();

        FakeServerBuilder::create(new WriteHandler($greeting))
            ->setUri($clientBuilder->getUri())
            ->start();

        $client = $clientBuilder->setOptions(['username' => 'guest'])->build();
        $connection = $client->getHandler()->getConnection();

        try {
            $client->ping();
        } catch (CommunicationFailed $e) {
            self::assertLessThan(Greeting::SIZE_BYTES, \strlen($greeting));
            self::assertTrue($connection->isClosed());

            return;
        } catch (UnexpectedResponse $e) {
            self::assertGreaterThanOrEqual(Greeting::SIZE_BYTES, \strlen($greeting));
            self::assertContains($e->getMessage(), ['Unable to decode salt', 'Salt is too short']);

            return;
        }

        self::fail();
    }
}

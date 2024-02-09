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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client;
use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Space;
use Tarantool\Client\Tests\SpyMiddleware;

final class ClientMiddlewareTest extends TestCase
{
    public function testSpaceInheritsMiddleware() : void
    {
        $middleware = SpyMiddleware::fromTraceId(1);

        // Cache the space
        $this->client->getSpaceById(Space::VSPACE_ID)->select(Criteria::key([]));

        $clientWithMiddleware = $this->client->withMiddleware($middleware);

        $clientWithMiddleware->ping();
        self::assertSame([1], $middleware->getTraceLogArray());

        $spaceWithMiddleware = $clientWithMiddleware->getSpaceById(Space::VSPACE_ID);

        $spaceWithMiddleware->select(Criteria::key([]));
        self::assertSame([1, 1], $middleware->getTraceLogArray());

        $spaceWithMiddleware->select(Criteria::key([]));
        self::assertSame([1, 1, 1], $middleware->getTraceLogArray());
    }

    /**
     * @doesNotPerformAssertions
     *
     * @lua fiber = require('fiber')
     * @lua function test() try_drop_user('foobar') fiber.sleep(.5) create_user('foobar', '') end
     * @lua fiber.create(test)
     */
    public function testAuthenticationRetrySucceeds() : void
    {
        $client = Client::fromOptions([
            'uri' => ClientBuilder::createFromEnv()->getUri(),
            'username' => 'foobar',
            'max_retries' => 5,
            // 5 linear retries with a difference of 100ms and equal jitter give
            // at worst 100/2 + 200/2 + 300/2 + 400/2 + 800/2 = 900ms in total (> 0.5)
        ]);

        $client->ping();
    }

    public function testAuthenticationRetryFails() : void
    {
        $client = Client::fromOptions([
            'uri' => ClientBuilder::createFromEnv()->getUri(),
            'username' => 'ghost',
            'max_retries' => 2,
        ]);

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessageMatches("/(User 'ghost' is not found|User not found or supplied credentials are invalid)/");

        $client->ping();
    }

    public function testRetry() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        $client = $clientBuilder->build();
        $retryableClient = $clientBuilder->setOptions(['max_retries' => 1])->build();

        $client->evaluate($luaInit = 'create_space("connection_retry")');
        // Trigger an error only on the first call
        $retryableClient->evaluate($luaCall = '
            if box.space.connection_retry then
                box.space.connection_retry:drop()
                box.error{code = 42, reason = "Foobar"}
            end
        ');

        $client->evaluate($luaInit);
        $this->expectException(RequestFailed::class);
        $client->evaluate($luaCall);
    }

    public function testNoRetriesOnUnexpectedResponse() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        $client = $clientBuilder->setOptions(['max_retries' => 5])->build();

        self::triggerUnexpectedResponse($client->getHandler(), new PingRequest());

        $this->expectException(UnexpectedResponse::class);
        $client->ping();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testReconnectOnBrokenConnection() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        $client = $clientBuilder->setConnectionOptions(['socket_timeout' => 1])->build();

        $client = $client->withMiddleware(RetryMiddleware::constant(1));
        $client = $client->withMiddleware(self::createBrokenConnectionMiddleware());

        $client->ping();
        $client->ping();
    }

    public function testThrowOnBrokenConnection() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        $client = $clientBuilder->setConnectionOptions(['socket_timeout' => 1])->build();

        $client = $client->withMiddleware(self::createBrokenConnectionMiddleware());

        $client->ping();

        $this->expectException(CommunicationFailed::class);
        $this->expectExceptionMessageMatches('/Error writing request:.+Send of .+ bytes failed/i');
        $client->ping();
    }

    private static function createBrokenConnectionMiddleware() : Middleware
    {
        return new class() implements Middleware {
            private $count = 0;

            public function process(Request $request, Handler $handler) : Response
            {
                if (1 === $this->count) {
                    $connection = $handler->getConnection();
                    stream_socket_shutdown(TestCase::getRawStream($connection), STREAM_SHUT_WR);
                }

                ++$this->count;

                // Suppress PHPUnit\Framework\Error\Notice:
                // fwrite(): send of 15 bytes failed with errno=32 Broken pipe
                return @$handler->handle($request);
            }
        };
    }
}

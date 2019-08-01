<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Space;

final class ClientMiddlewareTest extends TestCase
{
    public function testSpaceInheritsMiddleware() : void
    {
        $middleware = new class() implements Middleware {
            public $counter = 0;

            public function process(Request $request, Handler $handler) : Response
            {
                ++$this->counter;

                return $handler->handle($request);
            }
        };

        // cache the space
        $this->client->getSpaceById(Space::VSPACE_ID)->select(Criteria::key([]));

        $clientWithMiddleware = $this->client->withMiddleware($middleware);

        $clientWithMiddleware->ping();
        self::assertSame(1, $middleware->counter);

        $spaceWithMiddleware = $clientWithMiddleware->getSpaceById(Space::VSPACE_ID);

        $spaceWithMiddleware->select(Criteria::key([]));
        self::assertSame(2, $middleware->counter);

        $spaceWithMiddleware->select(Criteria::key([]));
        self::assertSame(3, $middleware->counter);
    }

    /**
     * @doesNotPerformAssertions
     *
     * @eval fiber = require('fiber')
     * @eval function test() try_drop_user('foobar') fiber.sleep(.5) create_user('foobar', '') end
     * @eval fiber.create(test)
     */
    public function testAuthenticationRetrySucceeds() : void
    {
        $client = Client::fromOptions([
            'uri' => ClientBuilder::createFromEnv()->getUri(),
            'username' => 'foobar',
            'max_retries' => 4, // 4 linear retries with a difference of 100 ms give 1 sec in total (> 0.5)
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
        $this->expectExceptionMessage("User 'ghost' is not found");

        $client->ping();
    }

    public function testRetry() : void
    {
        $clientBuilder = ClientBuilder::createFromEnv();
        $client = $clientBuilder->build();
        $retryableClient = $clientBuilder->setOptions(['max_retries' => 1])->build();

        $client->evaluate($luaInit = 'create_space("connection_retry")');
        // trigger an error only on the first call
        $retryableClient->evaluate($luaCall = '
            if box.space.connection_retry then
                box.space.connection_retry:drop() 
                box.error{code = 42, reason = "Foobar."}
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
}

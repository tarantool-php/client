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

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\Middleware;
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
}

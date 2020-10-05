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

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Tests\SpyMiddleware;
use Tarantool\PhpUnit\Client\TestDoubleClient;

final class ClientMiddlewareTest extends TestCase
{
    use TestDoubleClient;

    public function testWithMiddlewareAppendsMiddleware() : void
    {
        $trace = new \ArrayObject();
        $middleware1 = SpyMiddleware::fromTraceId(1, $trace);
        $middleware2 = SpyMiddleware::fromTraceId(2, $trace);
        $middleware3 = SpyMiddleware::fromTraceId(3, $trace);
        $middleware4 = SpyMiddleware::fromTraceId(4, $trace);

        $client = $this->createDummyClient()->withMiddleware($middleware1, $middleware2);
        $client = $client->withMiddleware($middleware3, $middleware4);

        $client->ping();

        self::assertSame([1, 2, 3, 4], $trace->getArrayCopy());
    }

    public function testWithPrependedMiddlewarePrependsMiddleware() : void
    {
        $trace = new \ArrayObject();
        $middleware1 = SpyMiddleware::fromTraceId(1, $trace);
        $middleware2 = SpyMiddleware::fromTraceId(2, $trace);
        $middleware3 = SpyMiddleware::fromTraceId(3, $trace);
        $middleware4 = SpyMiddleware::fromTraceId(4, $trace);

        $client = $this->createDummyClient()->withPrependedMiddleware($middleware1, $middleware2);
        $client = $client->withPrependedMiddleware($middleware3, $middleware4);

        $client->ping();

        self::assertSame([3, 4, 1, 2], $trace->getArrayCopy());
    }
}

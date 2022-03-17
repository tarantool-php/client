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

namespace Tarantool\Client\Tests\Unit\Handler;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Tests\SpyMiddleware;
use Tarantool\PhpUnit\Client\TestDoubleClient;

final class MiddlewareHandlerTest extends TestCase
{
    use TestDoubleClient;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Handler
     */
    private $handler;

    protected function setUp() : void
    {
        $this->request = $this->createMock(Request::class);
        $this->handler = $this->createDummyClient()->getHandler();
    }

    public function testAppendedMiddlewareAreExecutedInFifoOrder() : void
    {
        $trace = new \ArrayObject();
        $middleware1 = SpyMiddleware::fromTraceId(1, $trace);
        $middleware2 = SpyMiddleware::fromTraceId(2, $trace);

        $handler = MiddlewareHandler::append($this->handler, [$middleware1, $middleware2]);
        $handler->handle($this->request);

        self::assertSame([1, 2], $trace->getArrayCopy());
    }

    public function testAppendAppendsMiddleware() : void
    {
        $trace = new \ArrayObject();
        $middleware1 = SpyMiddleware::fromTraceId(1, $trace);
        $middleware2 = SpyMiddleware::fromTraceId(2, $trace);

        $handler = MiddlewareHandler::append($this->handler, [$middleware1]);
        $handler = MiddlewareHandler::append($handler, [$middleware2]);
        $handler->handle($this->request);

        self::assertSame([1, 2], $trace->getArrayCopy());
    }

    public function testPrependPrependsMiddleware() : void
    {
        $trace = new \ArrayObject();
        $middleware1 = SpyMiddleware::fromTraceId(1, $trace);
        $middleware2 = SpyMiddleware::fromTraceId(2, $trace);

        $handler = MiddlewareHandler::append($this->handler, [$middleware1]);
        $handler = MiddlewareHandler::prepend($handler, [$middleware2]);
        $handler->handle($this->request);

        self::assertSame([2, 1], $trace->getArrayCopy());
    }

    public function testMiddlewareRemainsAfterExecution() : void
    {
        $middleware = SpyMiddleware::fromTraceId(1);

        $handler = MiddlewareHandler::append($this->handler, [$middleware]);
        $handler->handle($this->request);
        $handler->handle($this->request);

        self::assertSame([1, 1], $middleware->getTraceLogArray());
    }
}

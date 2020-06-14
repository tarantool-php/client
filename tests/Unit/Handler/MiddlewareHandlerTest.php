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

namespace Tarantool\Client\Tests\Unit\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\Request;
use Tarantool\PhpUnit\Client\TestDoubleFactory;

final class MiddlewareHandlerTest extends TestCase
{
    /**
     * @var Request|MockObject
     */
    private $request;

    /**
     * @var Handler|MockObject
     */
    private $handler;

    protected function setUp() : void
    {
        $this->request = $this->createMock(Request::class);
        $this->handler = $this->createMock(Handler::class);
    }

    public function testCreateMiddlewareExecutionOrder() : void
    {
        $trace = new \ArrayObject();

        /** @var Middleware $middleware1 */
        $middleware1 = $this->createMock(Middleware::class);
        $middleware1->expects($this->once())->method('process')->willReturnCallback(
            static function (Request $request, Handler $handler) use (&$trace) {
                $trace[] = 1;

                return $handler->handle($request);
            }
        );

        /** @var Middleware $middleware2 */
        $middleware2 = $this->createMock(Middleware::class);
        $middleware2->expects($this->once())->method('process')->willReturnCallback(
            static function (Request $request, Handler $handler) use (&$trace) {
                $trace[] = 2;

                return $handler->handle($request);
            }
        );

        $this->handler->method('handle')->willReturn(TestDoubleFactory::createEmptyResponse());

        $handler = MiddlewareHandler::create($this->handler, $middleware1, $middleware2);
        $handler->handle($this->request);

        self::assertSame('1,2', implode(',', $trace->getArrayCopy()));
    }

    public function testMiddlewareRemainsAfterExecution() : void
    {
        $middlewareCallback = static function (Request $request, Handler $handler) {
            return $handler->handle($request);
        };

        /** @var Middleware $middleware */
        $middleware = $this->createMock(Middleware::class);
        $middleware->expects($this->exactly(2))->method('process')->willReturnCallback($middlewareCallback);

        $this->handler->method('handle')->willReturn(TestDoubleFactory::createEmptyResponse());

        $handler = MiddlewareHandler::create($this->handler, $middleware);
        $handler->handle($this->request);
        $handler->handle($this->request);
    }
}

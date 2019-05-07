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

namespace Tarantool\Client\Tests\Unit\Handler;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

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

        $this->handler->method('handle')->willReturn(new Response([], []));

        $handler = MiddlewareHandler::create($this->handler, $middleware1, $middleware2);
        $handler->handle($this->request);

        self::assertSame('2,1', implode(',', $trace->getArrayCopy()));
    }

    public function testCreateReusesHandler() : void
    {
        $middlewareCallback = static function (Request $request, Handler $handler) {
            return $handler->handle($request);
        };

        /** @var Middleware $middleware1 */
        $middleware1 = $this->createMock(Middleware::class);
        $middleware1->expects($this->once())->method('process')->willReturnCallback($middlewareCallback);

        /** @var Middleware $middleware2 */
        $middleware2 = $this->createMock(Middleware::class);
        $middleware2->expects($this->once())->method('process')->willReturnCallback($middlewareCallback);

        $this->handler->method('handle')->willReturn(new Response([], []));

        $handler1 = MiddlewareHandler::create($this->handler, $middleware1);
        self::assertNotSame($this->handler, $handler1);

        $handler2 = MiddlewareHandler::create($handler1, $middleware2);
        self::assertSame($handler1, $handler2);

        $handler2->handle($this->request);
    }
}

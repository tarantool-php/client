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

namespace Tarantool\Client\Tests\Unit\Middleware;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class RetryMiddlewareTest extends TestCase
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

    public function testSuccessfulRetry() : void
    {
        $response = new Response([], []);

        $this->handler->expects($this->exactly(3))->method('handle')
            ->will($this->onConsecutiveCalls(
                $this->throwException(new \RuntimeException()),
                $this->throwException(new ConnectionFailed()),
                $response
            ));

        $middleware = RetryMiddleware::custom(function (int $retries) : ?int {
            return 0;
        });

        self::assertSame($response, $middleware->process($this->request, $this->handler));
    }

    public function testUnsuccessfulRetries() : void
    {
        $this->handler->expects($this->exactly(4))->method('handle')
            ->willThrowException(new ConnectionFailed());

        $middleware = RetryMiddleware::custom(function (int $retries) : ?int {
            return $retries <= 3 ? 0 : null;
        });

        $this->expectException(ConnectionFailed::class);
        $middleware->process($this->request, $this->handler);
    }
}

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

namespace Tarantool\Client\Tests\Unit\Middleware;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Request\Request;
use Tarantool\PhpUnit\Client\TestDoubleFactory;

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
        $response = TestDoubleFactory::createEmptyResponse();

        $this->handler->expects(self::exactly(3))->method('handle')
            ->will(self::onConsecutiveCalls(
                self::throwException(new ConnectionFailed('unsuccessful op #1')),
                self::throwException(new CommunicationFailed('unsuccessful op #2')),
                $response
            ));

        $middleware = RetryMiddleware::custom(static function () : int {
            return 0;
        });

        self::assertSame($response, $middleware->process($this->request, $this->handler));
    }

    public function testUnsuccessfulRetries() : void
    {
        $this->handler->expects(self::exactly(4))->method('handle')
            ->willThrowException(new ConnectionFailed());

        $middleware = RetryMiddleware::custom(static function (int $retries) : ?int {
            return $retries <= 3 ? 0 : null;
        });

        $this->expectException(ConnectionFailed::class);
        $middleware->process($this->request, $this->handler);
    }

    public function testInfiniteRetriesBreak() : void
    {
        $this->handler->method('handle')
            ->willThrowException(new ConnectionFailed());

        $totalRetries = 0;
        $middleware = RetryMiddleware::custom(static function (int $retries) use (&$totalRetries) : int {
            $totalRetries = $retries;
            // always returning a value other than null
            // leads to an infinite retry loop
            return 0;
        });

        try {
            $middleware->process($this->request, $this->handler);
            self::fail(sprintf('"%s" exception was not thrown', ConnectionFailed::class));
        } catch (ConnectionFailed $e) {
            self::assertSame(10, $totalRetries);
        }
    }
}

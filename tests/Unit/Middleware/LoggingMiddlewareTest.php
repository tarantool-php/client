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

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\LoggingMiddleware;
use Tarantool\Client\Request\PingRequest;
use Tarantool\PhpUnit\Client\TestDoubleFactory;

final class LoggingMiddlewareTest extends TestCase
{
    public function testLogsRequestAndResponseWhenHandlingSucceeds() : void
    {
        $request = new PingRequest();
        $response = TestDoubleFactory::createEmptyResponse();
        $handler = $this->createMock(Handler::class);
        $handler->expects(self::once())->method('handle')->with($request)->willReturn($response);

        $messages = [];
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(2))->method('debug')
            ->willReturnCallback(static function (string $message, array $context) use (&$messages) : void {
                $messages[] = [$message, $context];
            });
        $logger->expects(self::never())->method('error');

        $result = (new LoggingMiddleware($logger))->process($request, $handler);

        self::assertSame($response, $result);
        self::assertSame('Starting handling request "ping"', $messages[0][0]);
        self::assertSame(['request' => $request], $messages[0][1]);
        self::assertSame('Finished handling request "ping"', $messages[1][0]);
        self::assertSame($request, $messages[1][1]['request']);
        self::assertSame($response, $messages[1][1]['response']);
        self::assertIsNumeric($messages[1][1]['duration_ms']);
    }

    public function testLogsAndRethrowsHandlerException() : void
    {
        $request = new PingRequest();
        $exception = new \RuntimeException('handler failed');
        $handler = $this->createMock(Handler::class);
        $handler->expects(self::once())->method('handle')->with($request)->willThrowException($exception);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('debug')
            ->with('Starting handling request "ping"', ['request' => $request]);
        $logger->expects(self::once())->method('error')
            ->with('Request "ping" failed', self::callback(static function (array $context) use ($request, $exception) : bool {
                return $request === ($context['request'] ?? null)
                    && $exception === ($context['exception'] ?? null)
                    && isset($context['duration_ms'])
                    && is_numeric($context['duration_ms']);
            }));

        try {
            (new LoggingMiddleware($logger))->process($request, $handler);
            self::fail('Expected the handler exception to be rethrown');
        } catch (\RuntimeException $actual) {
            self::assertSame($exception, $actual);
        }
    }
}

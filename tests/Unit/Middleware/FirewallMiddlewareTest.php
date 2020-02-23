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
use Tarantool\Client\Exception\RequestDenied;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\FirewallMiddleware;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\CallRequest;
use Tarantool\Client\Request\EvaluateRequest;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\SelectRequest;
use Tarantool\Client\RequestTypes;
use Tarantool\Client\Schema\IteratorTypes;
use Tarantool\Client\Tests\Unit\ResponseFactory;

final class FirewallMiddlewareTest extends TestCase
{
    /**
     * @var Handler|MockObject
     */
    private $handler;

    protected function setUp() : void
    {
        $this->handler = $this->createMock(Handler::class);
    }

    public function testEmptyAllowList() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::deny(RequestTypes::CALL);
        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAllow() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::allow(RequestTypes::PING);
        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAllowForbids() : void
    {
        $middleware = FirewallMiddleware::allow(RequestTypes::PING);

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', CallRequest::class));

        $middleware->process(new CallRequest('foo'), $this->handler);
    }

    public function testAndAllow() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::deny(RequestTypes::CALL)->andAllow(RequestTypes::PING);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAndAllowForbids() : void
    {
        $middleware = FirewallMiddleware::deny(RequestTypes::CALL)->andAllow(RequestTypes::PING);

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', EvaluateRequest::class));

        $middleware->process(new EvaluateRequest('return 42'), $this->handler);
    }

    public function testAndAllowOnly() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::allow(RequestTypes::CALL)->andAllowOnly(RequestTypes::PING);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAndAllowOnlyForbids() : void
    {
        $middleware = FirewallMiddleware::allow(RequestTypes::CALL)->andAllowOnly(RequestTypes::PING);

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', CallRequest::class));

        $middleware->process(new CallRequest('foo'), $this->handler);
    }

    public function testDeny() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::deny(RequestTypes::PING);

        $middleware->process(new CallRequest('foo'), $this->handler);
    }

    public function testDenyForbids() : void
    {
        $middleware = FirewallMiddleware::deny(RequestTypes::PING);

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', PingRequest::class));

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAndDeny() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::allow(RequestTypes::PING)->andDeny(RequestTypes::CALL);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAndDenyForbids() : void
    {
        $middleware = FirewallMiddleware::allow(RequestTypes::PING)->andDeny(RequestTypes::CALL);

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', CallRequest::class));

        $middleware->process(new CallRequest('foo'), $this->handler);
    }

    public function testAndDenyOnly() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::deny(RequestTypes::PING)->andDenyOnly(RequestTypes::CALL);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testAndDenyOnlyForbids() : void
    {
        $middleware = FirewallMiddleware::deny(RequestTypes::PING)->andDenyOnly(RequestTypes::CALL);

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', CallRequest::class));

        $middleware->process(new CallRequest('foo'), $this->handler);
    }

    /**
     * @dataProvider provideBlacklistPriorityData
     */
    public function testDenyHasPriority(Middleware $middleware) : void
    {
        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', PingRequest::class));

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function provideBlacklistPriorityData() : iterable
    {
        yield [FirewallMiddleware::allow(RequestTypes::PING)->andDeny(RequestTypes::PING)];
        yield [FirewallMiddleware::deny(RequestTypes::PING)->andAllow(RequestTypes::PING)];
    }

    public function testAllowReadOnly() : void
    {
        $this->handler->expects($this->exactly(3))->method('handle')
            ->willReturn(ResponseFactory::create());

        $middleware = FirewallMiddleware::allowReadOnly();

        $middleware->process(new AuthenticateRequest('12345678901234567890', 'guest'), $this->handler);
        $middleware->process(new PingRequest(), $this->handler);
        $middleware->process(new SelectRequest(1, 1, [], 0, 1, IteratorTypes::ALL), $this->handler);
    }

    public function testAllowReadOnlyForbids() : void
    {
        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is denied', EvaluateRequest::class));

        FirewallMiddleware::allowReadOnly()->process(new EvaluateRequest('return 42'), $this->handler);
    }
}

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
use Tarantool\Client\Exception\RequestForbidden;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\FirewallMiddleware;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Request\SelectRequest;
use Tarantool\Client\Response;
use Tarantool\Client\Schema\IteratorTypes;

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

    public function testAllowByDefault() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(new Response([], []));

        $middleware = new FirewallMiddleware([], []);
        $middleware->process(new FooRequest(), $this->handler);
    }

    public function testWhitelist() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(new Response([], []));

        $middleware = FirewallMiddleware::whitelist(FooRequest::class);
        $middleware->process(new FooRequest(), $this->handler);
    }

    public function testWithWhitelist() : void
    {
        $this->handler->expects($this->once())->method('handle')
            ->willReturn(new Response([], []));

        $middleware = FirewallMiddleware::blacklist(BarRequest::class)
            ->withWhitelist(FooRequest::class);

        $middleware->process(new FooRequest(), $this->handler);
    }

    public function testBlacklist() : void
    {
        $middleware = FirewallMiddleware::blacklist(FooRequest::class);

        $this->expectException(RequestForbidden::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is forbidden.', FooRequest::class));

        $middleware->process(new FooRequest(), $this->handler);
    }

    public function testWithBlacklist() : void
    {
        $middleware = FirewallMiddleware::whitelist(FooRequest::class)
            ->withBlacklist(BarRequest::class);

        $this->expectException(RequestForbidden::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is forbidden.', BarRequest::class));

        $middleware->process(new BarRequest(), $this->handler);
    }

    public function testChildRequestForbidden() : void
    {
        $middleware = FirewallMiddleware::whitelist(FooRequest::class);

        $this->expectException(RequestForbidden::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is forbidden.', BarRequest::class));

        $middleware->process(new BarRequest(), $this->handler);
    }

    public function testReadOnly() : void
    {
        $this->handler->expects($this->exactly(3))->method('handle')
            ->willReturn(new Response([], []));

        FirewallMiddleware::readOnly()->process(new AuthenticateRequest('12345678901234567890', 'guest'), $this->handler);
        FirewallMiddleware::readOnly()->process(new PingRequest(), $this->handler);
        FirewallMiddleware::readOnly()->process(new SelectRequest(1, 1, [], 0, 1, IteratorTypes::ALL), $this->handler);
    }

    public function testReadOnlyForbidsRequest() : void
    {
        $this->expectException(RequestForbidden::class);
        $this->expectExceptionMessage(sprintf('Request "%s" is forbidden.', FooRequest::class));

        FirewallMiddleware::readOnly()->process(new FooRequest(), $this->handler);
    }

    public function testWhitelistThrowsTypeError() : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf('Class "stdClass" should implement %s.', Request::class));

        FirewallMiddleware::whitelist(\stdClass::class);
    }

    public function testWithWhitelistThrowsTypeError() : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf('Class "stdClass" should implement %s.', Request::class));

        FirewallMiddleware::whitelist(FooRequest::class)->withWhitelist(\stdClass::class);
    }

    public function testBlacklistThrowsTypeError() : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf('Class "stdClass" should implement %s.', Request::class));

        FirewallMiddleware::blacklist(\stdClass::class);
    }

    public function testWithBlacklistThrowsTypeError() : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage(sprintf('Class "stdClass" should implement %s.', Request::class));

        FirewallMiddleware::blacklist(FooRequest::class)->withBlacklist(\stdClass::class);
    }
}

class FooRequest implements Request
{
    public function getType() : int
    {
        return 42;
    }

    public function getBody() : array
    {
        return [];
    }
}

class BarRequest extends FooRequest
{
}

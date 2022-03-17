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

namespace Tarantool\Client\Handler;

use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class MiddlewareHandler implements Handler
{
    /** @var Handler */
    private $handler;

    /** @var Middleware[] */
    private $middleware;

    /** @var int */
    private $index = 0;

    /**
     * @param Handler $handler
     * @param Middleware[] $middleware
     */
    private function __construct($handler, $middleware)
    {
        $this->handler = $handler;
        $this->middleware = $middleware;
    }

    /**
     * @param Middleware[] $middleware
     */
    public static function append(Handler $handler, array $middleware) : Handler
    {
        if (!$handler instanceof self) {
            return new self($handler, $middleware);
        }

        $handler = clone $handler;
        $handler->middleware = \array_merge($handler->middleware, $middleware);

        return $handler;
    }

    /**
     * @param Middleware[] $middleware
     */
    public static function prepend(Handler $handler, array $middleware) : Handler
    {
        if (!$handler instanceof self) {
            return new self($handler, $middleware);
        }

        $handler = clone $handler;
        $handler->middleware = \array_merge($middleware, $handler->middleware);

        return $handler;
    }

    public function handle(Request $request) : Response
    {
        if (!isset($this->middleware[$this->index])) {
            return $this->handler->handle($request);
        }

        $new = clone $this;
        ++$new->index;

        return $this->middleware[$this->index]->process($request, $new);
    }

    public function getConnection() : Connection
    {
        return $this->handler->getConnection();
    }

    public function getPacker() : Packer
    {
        return $this->handler->getPacker();
    }
}

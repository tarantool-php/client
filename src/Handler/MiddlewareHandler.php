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

    /** @var non-empty-array<int, Middleware> */
    private $middlewares;

    /** @var int */
    private $index = 0;

    /**
     * @param Handler $handler
     * @param non-empty-array<int, Middleware> $middlewares
     */
    private function __construct($handler, $middlewares)
    {
        $this->handler = $handler;
        $this->middlewares = $middlewares;
    }

    public static function create(Handler $handler, Middleware $middleware, Middleware ...$middlewares) : Handler
    {
        if (!$handler instanceof self) {
            return $middlewares
                ? new self($handler, \array_merge([$middleware], $middlewares))
                : new self($handler, [$middleware]);
        }

        $handler = clone $handler;
        $handler->middlewares[] = $middleware;
        if ($middlewares) {
            $handler->middlewares = \array_merge($handler->middlewares, $middlewares);
        }

        return $handler;
    }

    public function handle(Request $request) : Response
    {
        if (!isset($this->middlewares[$this->index])) {
            return $this->handler->handle($request);
        }

        $new = clone $this;
        ++$new->index;

        return $this->middlewares[$this->index]->process($request, $new);
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

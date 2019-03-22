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

namespace Tarantool\Client\Handler;

use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class MiddlewareHandler implements Handler
{
    private $middleware;
    private $handler;
    private $connection;
    private $packer;

    public function __construct(Middleware $middleware, Handler $handler)
    {
        $this->middleware = $middleware;
        $this->handler = $handler;
    }

    public static function create(Handler $handler, array $middlewares) : Handler
    {
        if (!$middlewares) {
            return $handler;
        }

        $middleware = \end($middlewares);

        while ($middleware) {
            $handler = new self($middleware, $handler);
            $middleware = \prev($middlewares);
        }

        return $handler;
    }

    public function getConnection() : Connection
    {
        return $this->connection ?: $this->connection = $this->handler->getConnection();
    }

    public function getPacker() : Packer
    {
        return $this->packer ?: $this->packer = $this->handler->getPacker();
    }

    public function handle(Request $request) : Response
    {
        return $this->middleware->process($request, $this->handler);
    }
}

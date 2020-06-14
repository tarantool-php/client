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

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Connection\Greeting;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class AuthenticationMiddleware implements Middleware
{
    private $username;
    private $password;

    /** @var Greeting|null */
    private $greeting;

    public function __construct(string $username, string $password = '')
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $greeting = $handler->getConnection()->open();

        if ($greeting->equals($this->greeting)) {
            return $handler->handle($request);
        }

        $handler->handle(new AuthenticateRequest(
            $greeting->getSalt(),
            $this->username,
            $this->password
        ));

        $this->greeting = $greeting;

        return $handler->handle($request);
    }
}

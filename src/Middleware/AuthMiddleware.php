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

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class AuthMiddleware implements Middleware
{
    private $username;
    private $password;

    public function __construct(string $username, string $password = '')
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $connection = $handler->getConnection();

        if ($connection->isClosed()) {
            $handler->handle(new AuthenticateRequest(
                $connection->open(),
                $this->username,
                $this->password
            ));
        }

        return $handler->handle($request);
    }
}

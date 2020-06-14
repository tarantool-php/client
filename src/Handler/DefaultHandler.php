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
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class DefaultHandler implements Handler
{
    private $connection;
    private $packer;

    public function __construct(Connection $connection, Packer $packer)
    {
        $this->connection = $connection;
        $this->packer = $packer;
    }

    public function handle(Request $request) : Response
    {
        $packet = $this->packer->pack($request, $sync = \mt_rand());
        $this->connection->open();
        $packet = $this->connection->send($packet);

        $response = $this->packer->unpack($packet);

        if ($sync !== $response->getSync()) {
            $this->connection->close();
            throw UnexpectedResponse::outOfSync($sync, $response->getSync());
        }

        if ($response->isError()) {
            throw RequestFailed::fromErrorResponse($response);
        }

        return $response;
    }

    public function getConnection() : Connection
    {
        return $this->connection;
    }

    public function getPacker() : Packer
    {
        return $this->packer;
    }
}

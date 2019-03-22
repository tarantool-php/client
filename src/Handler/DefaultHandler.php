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
use Tarantool\Client\Exception\RequestFailed;
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

    public function getConnection() : Connection
    {
        return $this->connection;
    }

    public function getPacker() : Packer
    {
        return $this->packer;
    }

    public function handle(Request $request) : Response
    {
        if ($this->connection->isClosed()) {
            $this->connection->open();
        }

        $data = $this->packer->pack($request);
        $data = $this->connection->send($data);

        $response = $this->packer->unpack($data);
        if (!$response->isError()) {
            return $response;
        }

        throw RequestFailed::fromErrorResponse($response);
    }
}

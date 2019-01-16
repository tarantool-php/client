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

namespace Tarantool\Client;

use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Request\Authenticate;
use Tarantool\Client\Request\Call;
use Tarantool\Client\Request\Evaluate;
use Tarantool\Client\Request\Execute;
use Tarantool\Client\Request\Ping;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Schema\Index;
use Tarantool\Client\Schema\Space;

final class Client
{
    private $connection;
    private $packer;
    private $salt;
    private $username;
    private $password;
    private $spaces = [];

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

    public function connect() : void
    {
        $this->salt = $this->connection->open();

        if ($this->username) {
            $this->authenticate($this->username, $this->password);
        }
    }

    public function disconnect() : void
    {
        $this->connection->close();
        $this->salt = null;
    }

    public function isDisconnected() : bool
    {
        return $this->connection->isClosed() || !$this->salt;
    }

    public function authenticate(string $username, string $password = '') : void
    {
        if ($this->isDisconnected()) {
            $this->salt = $this->connection->open();
        }

        $request = new Authenticate($this->salt, $username, $password);
        $this->sendRequest($request);

        $this->username = $username;
        $this->password = $password;

        $this->flushSpaces();
    }

    public function ping() : void
    {
        $request = new Ping();

        $this->sendRequest($request);
    }

    public function getSpace(string $spaceName) : Space
    {
        if (isset($this->spaces[$spaceName])) {
            return $this->spaces[$spaceName];
        }

        $spaceId = $this->getSpaceIdByName($spaceName);

        return $this->spaces[$spaceName] = $this->spaces[$spaceId] = new Space($this, $spaceId);
    }

    public function getSpaceById(int $spaceId) : Space
    {
        if (isset($this->spaces[$spaceId])) {
            return $this->spaces[$spaceId];
        }

        return $this->spaces[$spaceId] = new Space($this, $spaceId);
    }

    public function call(string $funcName, ...$args) : array
    {
        $request = new Call($funcName, $args);

        return $this->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function executeQuery(string $sql, array $params = []) : SqlQueryResult
    {
        $request = new Execute($sql, $params);
        $response = $this->sendRequest($request);

        return new SqlQueryResult(
            $response->getBodyField(IProto::DATA),
            $response->getBodyField(IProto::METADATA)
        );
    }

    public function executeUpdate(string $sql, array $params = []) : int
    {
        $request = new Execute($sql, $params);

        return $this->sendRequest($request)->getBodyField(IProto::SQL_INFO)[0];
    }

    public function evaluate(string $expr, ...$args) : array
    {
        $request = new Evaluate($expr, $args);

        return $this->sendRequest($request)->getBodyField(IProto::DATA);
    }

    public function flushSpaces() : void
    {
        $this->spaces = [];
    }

    public function sendRequest(Request $request) : Response
    {
        if ($this->connection->isClosed()) {
            $this->connect();
        }

        $data = $this->packer->pack($request);
        $data = $this->connection->send($data);

        $response = $this->packer->unpack($data);
        if (!$response->isError()) {
            return $response;
        }

        throw RequestFailed::fromErrorResponse($response);
    }

    private function getSpaceIdByName(string $spaceName) : int
    {
        $schema = $this->getSpaceById(Space::VSPACE_ID);
        $data = $schema->select([$spaceName], Index::SPACE_NAME);

        if (empty($data)) {
            throw RequestFailed::unknownSpace($spaceName);
        }

        return $data[0][0];
    }
}

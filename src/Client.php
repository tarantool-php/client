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
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\CallRequest;
use Tarantool\Client\Request\EvaluateRequest;
use Tarantool\Client\Request\ExecuteRequest;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response\BinaryResponse;
use Tarantool\Client\Response\RawResponse;
use Tarantool\Client\Response\SqlQueryResponse;
use Tarantool\Client\Response\SqlUpdateResponse;
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

    public function authenticate(string $username, string $password = '') : BinaryResponse
    {
        if ($this->isDisconnected()) {
            $this->salt = $this->connection->open();
        }

        $request = new AuthenticateRequest($this->salt, $username, $password);
        $rawResponse = $this->sendRequest($request);

        $this->username = $username;
        $this->password = $password;

        $this->flushSpaces();

        return BinaryResponse::createFromRaw($rawResponse);
    }

    public function ping() : BinaryResponse
    {
        $request = new PingRequest();

        return BinaryResponse::createFromRaw($this->sendRequest($request));
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

    public function call(string $funcName, ...$args) : BinaryResponse
    {
        $request = new CallRequest($funcName, $args);

        return BinaryResponse::createFromRaw($this->sendRequest($request));
    }

    public function executeQuery(string $sql, array $params = []) : SqlQueryResponse
    {
        $request = new ExecuteRequest($sql, $params);

        return SqlQueryResponse::createFromRaw($this->sendRequest($request));
    }

    public function executeUpdate(string $sql, array $params = []) : SqlUpdateResponse
    {
        $request = new ExecuteRequest($sql, $params);

        return SqlUpdateResponse::createFromRaw($this->sendRequest($request));
    }

    public function evaluate(string $expr, array $args = []) : BinaryResponse
    {
        $request = new EvaluateRequest($expr, $args);

        return BinaryResponse::createFromRaw($this->sendRequest($request));
    }

    public function flushSpaces() : void
    {
        $this->spaces = [];
    }

    public function sendRequest(Request $request) : RawResponse
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

        throw new Exception(
            $response->getBodyField(IProto::ERROR),
            $response->getHeaderField(IProto::CODE) & (RawResponse::TYPE_ERROR - 1)
        );
    }

    private function getSpaceIdByName(string $spaceName) : int
    {
        $schema = $this->getSpaceById(Space::VSPACE);
        $response = $schema->select([$spaceName], Index::SPACE_NAME);
        $data = $response->getData();

        if (empty($data)) {
            throw new Exception("Space '$spaceName' does not exist");
        }

        return $data[0][0];
    }
}

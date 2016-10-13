<?php

namespace Tarantool\Client;

use React\Promise\Deferred;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\CallRequest;
use Tarantool\Client\Request\EvaluateRequest;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Schema\Index;
use Tarantool\Client\Schema\Space;

class ReactClient implements Client
{
    private $connection;
    private $salt;
    private $username;
    private $password;
    private $spaces = [];

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function connect()
    {
        $this->salt = $this->connection->open();

        if ($this->username) {
            $this->authenticate($this->username, $this->password);
        }
    }

    public function disconnect()
    {
        $this->connection->close();
        $this->salt = null;
    }

    public function isDisconnected()
    {
        return $this->connection->isClosed() || !$this->salt;
    }

    public function authenticate($username, $password = null)
    {
        if ($this->isDisconnected()) {
            $this->salt = $this->connection->open();
        }

        $request = new AuthenticateRequest($this->salt, $username, $password);
        $response = $this->sendRequest($request);

        $this->username = $username;
        $this->password = $password;

        $this->flushSpaces();

        return $response;
    }

    public function ping()
    {
        $request = new PingRequest();

        return $this->sendRequest($request);
    }

    public function getSpace($space)
    {
        if (isset($this->spaces[$space])) {
            return $this->spaces[$space];
        }

        if (!is_string($space)) {
            return $this->spaces[$space] = new Space($this, $space);
        }

        $deferred = new Deferred();

        $this->getSpaceByName($space)->then(function($data) use ($deferred, $space) {
            if (empty($data)) {
                throw new Exception("Space '$space' does not exist");
            }

            $spaceId = $data[0][0];

            $this->spaces[$space] = $this->spaces[$spaceId] = new Space($this, $spaceId);
            $deferred->resolve($this->spaces[$space]);
        });

        return $deferred->promise();
    }

    public function call($funcName, array $args = [])
    {
        $request = new CallRequest($funcName, $args);

        return $this->sendRequest($request);
    }

    public function evaluate($expr, array $args = [])
    {
        $request = new EvaluateRequest($expr, $args);

        return $this->sendRequest($request);
    }

    public function flushSpaces()
    {
        $this->spaces = [];
    }

    public function sendRequest(Request $request)
    {
        if ($this->connection->isClosed()) {
            $this->connect();
        }

        return $this->connection->send($request);
    }

    private function getSpaceByName($spaceName)
    {
        $schema = $this->getSpace(Space::VSPACE);

        return $schema->select([$spaceName], Index::SPACE_NAME);
    }
}

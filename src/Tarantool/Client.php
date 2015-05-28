<?php

namespace Tarantool;

use Tarantool\Connection\Connection;
use Tarantool\Encoder\Encoder;
use Tarantool\Encoder\PeclEncoder;
use Tarantool\Exception\Exception;
use Tarantool\Request\AuthenticateRequest;
use Tarantool\Request\CallRequest;
use Tarantool\Request\EvaluateRequest;
use Tarantool\Request\PingRequest;
use Tarantool\Request\Request;
use Tarantool\Schema\Index;
use Tarantool\Schema\Space;

class Client
{
    private $connection;
    private $encoder;
    private $salt;
    private $username;
    private $password;
    private $spaces = [];

    /**
     * @param Connection   $connection
     * @param Encoder|null $encoder
     */
    public function __construct(Connection $connection, Encoder $encoder = null)
    {
        $this->connection = $connection;
        $this->encoder = $encoder ?: new PeclEncoder();
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

    public function authenticate($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        if ($this->isDisconnected()) {
            $this->salt = $this->connection->open();
        }

        $request = new AuthenticateRequest($this->salt, $username, $password);

        return $this->sendRequest($request);
    }

    public function ping()
    {
        $request = new PingRequest();

        return $this->sendRequest($request);
    }

    /**
     * @param string|int $space
     *
     * @return Space
     *
     * @throws Exception
     */
    public function getSpace($space)
    {
        if (isset($this->spaces[$space])) {
            return $this->spaces[$space];
        }

        if (is_string($space)) {
            $schema = new Space($this, Space::SPACE);
            $response = $schema->select([$space], Index::SPACE_NAME);
            $data = $response->getData();

            if (empty($data)) {
                throw new Exception("Space '$space' does not exist");
            }

            $spaceName = $space;
            $space = $data[0][0];
        }

        $this->spaces[$space] = new Space($this, $space);

        if (isset($spaceName)) {
            $this->spaces[$spaceName] =  $this->spaces[$space];
        }

        return $this->spaces[$space];
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

        $data = $this->encoder->encode($request);
        $data = $this->connection->send($data);

        return $this->encoder->decode($data);
    }
}

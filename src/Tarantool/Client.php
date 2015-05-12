<?php

namespace Tarantool;

use Tarantool\Connection\Connection;
use Tarantool\Encoder\Encoder;
use Tarantool\Encoder\PeclEncoder;
use Tarantool\Request\AuthenticateRequest;
use Tarantool\Request\CallRequest;
use Tarantool\Request\DeleteRequest;
use Tarantool\Request\EvaluateRequest;
use Tarantool\Request\InsertRequest;
use Tarantool\Request\PingRequest;
use Tarantool\Request\ReplaceRequest;
use Tarantool\Request\SelectRequest;
use Tarantool\Request\UpdateRequest;
use Tarantool\Schema\Schema;

class Client
{
    private $connection;
    private $encoder;
    private $schema;
    private $salt;
    private $username;
    private $password;

    /**
     * @param Connection|null $connection
     * @param Encoder|null    $encoder
     */
    public function __construct(Connection $connection, Encoder $encoder = null)
    {
        $this->connection = $connection ?: new SocketConnection();
        $this->encoder = $encoder ?: new PeclEncoder();
        $this->schema = new Schema($this);
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function connect()
    {
        $this->salt = $this->connection->connect();

        if ($this->username) {
            $this->authenticate($this->username, $this->password);
        }
    }

    public function disconnect()
    {
        $this->connection->disconnect();
        $this->salt = null;
    }

    public function authenticate($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        if (!$this->connection->isConnected()) {
            $this->salt = $this->connection->connect();
        }

        $request = new AuthenticateRequest($this->salt, $username, $password);

        return $this->sendRequest($request);
    }

    public function ping()
    {
        $request = new PingRequest();

        return $this->sendRequest($request);
    }

    public function select($space, array $key = null, $index = null, $limit = null, $offset = null, $iteratorType = null)
    {
        $key = null === $key ? [] : $key;
        $offset = null === $offset ? 0 : $offset;
        $limit = null === $limit ? 0xffffffff : $limit;
        $iteratorType = null === $iteratorType ? 0 : $iteratorType;

        $space = $this->normalizeSpace($space);
        $index = null === $index ? 0 : $index;
        $index = $this->normalizeIndex($space, $index);

        $request = new SelectRequest($space, $index, $key, $offset, $limit, $iteratorType);

        return $this->sendRequest($request);
    }

    public function insert($space, array $values)
    {
        $space = $this->normalizeSpace($space);
        $request = new InsertRequest($space, $values);

        return $this->sendRequest($request);
    }

    public function replace($space, array $values)
    {
        $space = $this->normalizeSpace($space);
        $request = new ReplaceRequest($space, $values);

        return $this->sendRequest($request);
    }

    public function update($space, $key, array $operations, $index = null)
    {
        $space = $this->normalizeSpace($space);
        $index = null === $index ? 0 : $index;
        $index = $this->normalizeIndex($space, $index);

        $request = new UpdateRequest($space, $index, $key, $operations);

        return $this->sendRequest($request);
    }

    public function delete($space, array $key, $index = null)
    {
        $space = $this->normalizeSpace($space);
        $index = null === $index ? 0 : $index;
        $index = $this->normalizeIndex($space, $index);

        $request = new DeleteRequest($space, $index, $key);

        return $this->sendRequest($request);
    }

    public function call($funcName, array $args = [])
    {
        $request = new CallRequest($funcName, $args);

        return $this->sendRequest($request);
    }

    public function flushSchema()
    {
        $this->schema->flush();
    }

    public function evaluate($expr, array $args = [])
    {
        $request = new EvaluateRequest($expr, $args);

        return $this->sendRequest($request);
    }

    private function sendRequest($request)
    {
        if (!$this->connection->isConnected()) {
            $this->connect();
        }

        $data = $this->encoder->encode($request);
        $data = $this->connection->send($data);

        return $this->encoder->decode($data);
    }

    private function normalizeSpace($space)
    {
        if (is_string($space)) {
            return $this->schema->getSpace($space)->getId();
        }

        return $space;
    }

    private function normalizeIndex($space, $index)
    {
        if (is_string($index)) {
            return $this->schema->getIndex($space, $index)->getId();
        }

        return $index;
    }
}

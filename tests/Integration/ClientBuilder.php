<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Client as TarantoolClient;
use Tarantool\Connection\Connection;
use Tarantool\Connection\StreamConnection;
use Tarantool\Packer\PeclLitePacker;
use Tarantool\Packer\PeclPacker;
use Tarantool\Packer\PurePacker;
use Tarantool\Tests\Adapter\Tarantool;

class ClientBuilder
{
    const CLIENT_PURE = 'pure';
    const CLIENT_PECL = 'pecl';

    const CONN_TCP = 'tcp';
    const CONN_UNIX = 'unix';

    const PACKER_PURE = 'pure';
    const PACKER_PECL = 'pecl';
    const PACKER_PECL_LITE = 'pecl_lite';

    private $client;
    private $packer;
    private $connection;
    private $connectionOptions;
    private $host;
    private $port;
    private $unixSocket;

    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    public function setPacker($packer)
    {
        $this->packer = $packer;

        return $this;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function setConnectionOptions(array $options)
    {
        $this->connectionOptions = $options;

        return $this;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    public function setUnixSocket($unixSocket)
    {
        $this->unixSocket = $unixSocket;

        return $this;
    }

    public function build()
    {
        if (self::CLIENT_PECL === $this->client) {
            return new Tarantool($this->host, $this->port);
        }

        $connection = $this->createConnection();
        $packer = $this->createPacker();

        if (self::CLIENT_PURE === $this->client) {
            return new TarantoolClient($connection, $packer);
        }

        throw new \UnexpectedValueException(sprintf('"%s" client is not supported.', $this->client));
    }

    private function createConnection()
    {
        if ($this->connection instanceof Connection) {
            return $this->connection;
        }

        if (self::CONN_TCP === $this->connection) {
            return new StreamConnection(
                sprintf('tcp://%s:%s', $this->host, $this->port),
                $this->connectionOptions
            );
        }

        if (self::CONN_UNIX === $this->connection) {
            return new StreamConnection('unix://'.$this->unixSocket, $this->connectionOptions);
        }

        throw new \UnexpectedValueException(sprintf('"%s" connection is not supported.', $this->connection));
    }

    private function createPacker()
    {
        if (self::PACKER_PURE === $this->packer) {
            return new PurePacker();
        }

        if (self::PACKER_PECL === $this->packer) {
            return new PeclPacker();
        }

        if (self::PACKER_PECL_LITE === $this->packer) {
            return new PeclLitePacker();
        }

        throw new \UnexpectedValueException(sprintf('"%s" packer is not supported.', $this->packer));
    }
}

<?php

namespace Tarantool\Connection;

use Tarantool\Exception\ConnectionException;
use Tarantool\IProto;
use Tarantool\Packer\PackUtils;

class SocketConnection implements Connection
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 3301;

    private $host;
    private $port;
    private $socket;

    /**
     * @param string|null $host Default to 'localhost'.
     * @param int|null    $port Default to 3301.
     */
    public function __construct($host = null, $port = null)
    {
        $this->host = null === $host ? self::DEFAULT_HOST : $host;
        $this->port = null === $port ? self::DEFAULT_PORT : $port;
    }

    public function open()
    {
        $this->close();

        if (false === $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) {
            throw new ConnectionException(sprintf(
                'Unable to create socket: %s.', socket_strerror(socket_last_error())
            ));
        }

        if (false === @socket_connect($socket, $this->host, $this->port)) {
            throw new ConnectionException(sprintf(
                'Unable to connect: %s.', socket_strerror(socket_last_error($socket))
            ));
        }

        $this->socket = $socket;

        $greeting = socket_read($socket, IProto::GREETING_SIZE);

        return IProto::parseSalt($greeting);
    }

    public function close()
    {
        if ($this->socket) {
            socket_close($this->socket);
            $this->socket = null;
        }
    }

    public function isClosed()
    {
        return null === $this->socket;
    }

    public function send($data)
    {
        if (false === socket_write($this->socket, $data, strlen($data))) {
            throw $this->createConnectionException();
        }

        $length = null;
        if (false === socket_recv($this->socket, $length, IProto::LENGTH_SIZE, MSG_WAITALL)) {
            throw $this->createConnectionException();
        }

        $length = PackUtils::unpackLength($length);

        $data = null;
        if (false === socket_recv($this->socket, $data, $length, MSG_WAITALL)) {
            throw $this->createConnectionException();
        }

        return $data;
    }

    private function createConnectionException()
    {
        $errorCode = socket_last_error($this->socket);

        return new ConnectionException(socket_strerror($errorCode), $errorCode);
    }
}

<?php

namespace Tarantool\Connection;

use Tarantool\Exception\ConnectionException;
use Tarantool\IProto;
use Tarantool\Packer\PackUtils;

class StreamConnection implements Connection
{
    const DEFAULT_URI = 'tcp://127.0.0.1:3301';

    private $uri;

    private $options = [
        'connect_timeout' => 5.0,
        'socket_timeout' => 10.0,
        'persistent' => false,
    ];

    private $stream;

    public function __construct($uri = null, array $options = null)
    {
        $this->uri = null === $uri ? self::DEFAULT_URI : $uri;

        if ($options) {
            $this->options = $options + $this->options;
        }
    }

    public static function createFromUri($uri, array $options = null)
    {
        return new self($uri, $options);
    }

    public static function createFromHostPort($host, $port = null, array $options = null)
    {
        return new self(sprintf("tcp://%s:%s", $host, $port), $options);
    }

    public function open()
    {
        $this->close();

        $flags = STREAM_CLIENT_CONNECT;

        if ($this->options['persistent']) {
            $flags |= STREAM_CLIENT_PERSISTENT;
        }

        $stream = @stream_socket_client($this->uri, $errorCode, $errorMessage, (float) $this->options['connect_timeout'], $flags);

        if (false === $stream) {
            throw new ConnectionException(sprintf('Unable to connect: %s.', $errorMessage));
        }

        $this->stream = $stream;
        stream_set_timeout($this->stream, $this->options['socket_timeout']);

        if (!$greeting = stream_get_contents($this->stream, IProto::GREETING_SIZE)) {
            throw new ConnectionException('Unable to read greeting.');
        }

        return IProto::parseGreeting($greeting);
    }

    public function close()
    {
        if ($this->stream) {
            // is_resource($this->stream)
            fclose($this->stream);
            $this->stream = null;
        }
    }

    public function isClosed()
    {
        return null === $this->stream;
    }

    public function send($data)
    {
        if (!fwrite($this->stream, $data)) {
            throw new ConnectionException('Unable to write request.');
        }

        if (!$length = stream_get_contents($this->stream, IProto::LENGTH_SIZE)) {
            throw new ConnectionException('Unable to read response length.');
        }

        $length = PackUtils::unpackLength($length);

        if (!$data = stream_get_contents($this->stream, $length)) {
            throw new ConnectionException('Unable to read response.');
        }

        return $data;
    }
}

<?php

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\ConnectionException;
use Tarantool\Client\IProto;
use Tarantool\Client\Packer\PackUtils;

class StreamConnection implements Connection
{
    const DEFAULT_URI = 'tcp://127.0.0.1:3301';

    private $uri;

    private $options = [
        'connect_timeout' => 10.0,
        'socket_timeout' => 10.0,
    ];

    private $stream;

    public function __construct($uri = null, array $options = null)
    {
        $this->uri = null === $uri ? self::DEFAULT_URI : $uri;

        if ($options) {
            $this->options = $options + $this->options;
        }
    }

    public function open()
    {
        $this->close();

        $stream = @stream_socket_client($this->uri, $errorCode, $errorMessage, (float) $this->options['connect_timeout']);
        if (false === $stream) {
            throw new ConnectionException(sprintf('Unable to connect to %s: %s.', $this->uri, $errorMessage));
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

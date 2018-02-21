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

        // TODO
        // use stream_context_create() for php 7.1+
        // https://github.com/php/php-src/blob/6053987bc27e8dede37f437193a5cad448f99bce/ext/standard/tests/streams/stream_context_tcp_nodelay.phpt

        if (isset($this->options['tcp_nodelay']) && function_exists('socket_import_stream')) {
            $socket = socket_import_stream($this->stream);
            socket_set_option($socket, SOL_TCP, TCP_NODELAY, (int) $this->options['tcp_nodelay']);
        }

        $greeting = $this->read(IProto::GREETING_SIZE, 'Unable to read greeting.');

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
        return !\is_resource($this->stream);
    }

    public function send($data)
    {
        if (!fwrite($this->stream, $data)) {
            throw new ConnectionException('Unable to write request.');
        }

        $length = $this->read(IProto::LENGTH_SIZE, 'Unable to read response length.');
        $length = PackUtils::unpackLength($length);

        return $this->read($length, 'Unable to read response.');
    }

    private function read($length, $errorMessage)
    {
        if ($data = stream_get_contents($this->stream, $length)) {
            return $data;
        }

        $meta = stream_get_meta_data($this->stream);
        throw new ConnectionException($meta['timed_out'] ? 'Read timed out.' : $errorMessage);
    }
}

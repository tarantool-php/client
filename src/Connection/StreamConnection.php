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

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\ConnectionException;
use Tarantool\Client\IProto;
use Tarantool\Client\Packer\PackUtils;

final class StreamConnection implements Connection
{
    private const DEFAULT_URI = 'tcp://127.0.0.1:3301';

    private $uri;

    private $options = [
        'connect_timeout' => 5,
        'socket_timeout' => 5,
        'tcp_nodelay' => true,
    ];

    private $stream;

    public function __construct(string $uri = self::DEFAULT_URI, array $options = [])
    {
        $this->uri = $uri;

        if ($options) {
            $this->options = $options + $this->options;
        }
    }

    public function open() : string
    {
        $this->close();

        $stream = @\stream_socket_client(
            $this->uri,
            $errorCode,
            $errorMessage,
            (float) $this->options['connect_timeout'],
            \STREAM_CLIENT_CONNECT,
            \stream_context_create(['socket' => ['tcp_nodelay' => (bool) $this->options['tcp_nodelay']]])
        );

        if (false === $stream) {
            throw new ConnectionException(\sprintf('Unable to connect to %s: %s.', $this->uri, $errorMessage));
        }

        $this->stream = $stream;
        \stream_set_timeout($this->stream, $this->options['socket_timeout']);

        $greeting = $this->read(IProto::GREETING_SIZE, 'Unable to read greeting.');

        return IProto::parseGreeting($greeting);
    }

    public function close() : void
    {
        if ($this->stream) {
            \fclose($this->stream);
            $this->stream = null;
        }
    }

    public function isClosed() : bool
    {
        return !\is_resource($this->stream);
    }

    public function send(string $data) : string
    {
        if (!\fwrite($this->stream, $data)) {
            throw new ConnectionException('Unable to write request.');
        }

        $length = $this->read(IProto::LENGTH_SIZE, 'Unable to read response length.');
        $length = PackUtils::unpackLength($length);

        return $this->read($length, 'Unable to read response.');
    }

    private function read(int $length, string $errorMessage) : string
    {
        if ($data = \stream_get_contents($this->stream, $length)) {
            return $data;
        }

        $meta = \stream_get_meta_data($this->stream);
        throw new ConnectionException($meta['timed_out'] ? 'Read timed out.' : $errorMessage);
    }
}

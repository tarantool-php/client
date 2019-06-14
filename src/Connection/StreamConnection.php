<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Greeting;
use Tarantool\Client\Packer\PacketLength;

final class StreamConnection implements Connection
{
    public const DEFAULT_URI = 'tcp://127.0.0.1:3301';

    private const DEFAULT_OPTIONS = [
        'connect_timeout' => 5,
        'socket_timeout' => 5,
        'tcp_nodelay' => true,
        'persistent' => false,
    ];

    private $stream;
    private $streamContext;
    private $uri;
    private $options;
    private $greeting;

    private function __construct(string $uri, array $options)
    {
        $this->uri = $uri;
        $this->options = $options + self::DEFAULT_OPTIONS;
    }

    public static function createTcp(string $uri = self::DEFAULT_URI, array $options = []) : self
    {
        $self = new self($uri, $options);

        if ($self->options['tcp_nodelay'] ?? false) {
            $self->streamContext = \stream_context_create(['socket' => ['tcp_nodelay' => true]]);
        }

        return $self;
    }

    public static function createUds(string $uri, array $options = []) : self
    {
        return new self($uri, $options);
    }

    public static function create(string $uri, array $options = []) : self
    {
        return 0 === \strpos($uri, 'unix://')
            ? self::createUds($uri, $options)
            : self::createTcp($uri, $options);
    }

    public function open() : ?Greeting
    {
        if (\is_resource($this->stream)) {
            return $this->greeting;
        }

        $flags = $this->options['persistent']
            ? \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_PERSISTENT
            : \STREAM_CLIENT_CONNECT;

        $stream = $this->streamContext ? @\stream_socket_client(
            $this->uri,
            $errorCode,
            $errorMessage,
            (float) $this->options['connect_timeout'],
            $flags,
            $this->streamContext
        ) : @\stream_socket_client(
            $this->uri,
            $errorCode,
            $errorMessage,
            (float) $this->options['connect_timeout'],
            $flags
        );

        if (false === $stream) {
            throw ConnectionFailed::fromUriAndReason($this->uri, $errorMessage);
        }

        $this->stream = $stream;
        \stream_set_timeout($this->stream, $this->options['socket_timeout']);

        if ($this->options['persistent'] && \ftell($this->stream)) {
            return $this->greeting;
        }

        $greeting = $this->read(Greeting::SIZE_BYTES, 'Unable to read greeting.');

        return $this->greeting = Greeting::parse($greeting);
    }

    public function close() : void
    {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }

        $this->stream = null;
        $this->greeting = null;
    }

    public function isClosed() : bool
    {
        return !\is_resource($this->stream);
    }

    public function send(string $data) : string
    {
        if (!\fwrite($this->stream, $data)) {
            throw new CommunicationFailed('Unable to write request.');
        }

        $length = $this->read(PacketLength::SIZE_BYTES, 'Unable to read response length.');
        $length = PacketLength::unpack($length);

        return $this->read($length, 'Unable to read response.');
    }

    private function read(int $length, string $errorMessage) : string
    {
        if ($data = \stream_get_contents($this->stream, $length)) {
            return $data;
        }

        $meta = \stream_get_meta_data($this->stream);
        throw new CommunicationFailed($meta['timed_out'] ? 'Read timed out.' : $errorMessage);
    }
}

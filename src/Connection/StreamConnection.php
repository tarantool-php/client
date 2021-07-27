<?php

/**
 * This file is part of the tarantool/client package.
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

    /** @var resource|null */
    private $stream;

    /** @var resource|null */
    private $streamContext;

    /** @var string */
    private $uri;

    /** @var non-empty-array<string, mixed> */
    private $options;

    /** @var Greeting|null */
    private $greeting;

    /**
     * @param string $uri
     * @param array<string, mixed> $options
     */
    private function __construct($uri, $options)
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

    public function open() : Greeting
    {
        if ($this->greeting) {
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
        $socketTimeoutMicroseconds = 0;
        $socketTimeoutSeconds = (int) ($this->options['socket_timeout']);
        if (\is_float($this->options['socket_timeout'])) {
            $socketTimeoutMicroseconds = (int) ($this->options['socket_timeout'] * 1000000);
            $socketTimeoutMicroseconds -= $socketTimeoutSeconds * 1000000;
        }
        \stream_set_timeout($this->stream, $socketTimeoutSeconds, $socketTimeoutMicroseconds);

        if ($this->options['persistent'] && \ftell($this->stream)) {
            return $this->greeting = Greeting::unknown();
        }

        $greeting = $this->read(Greeting::SIZE_BYTES, 'Unable to read greeting');

        return $this->greeting = Greeting::parse($greeting);
    }

    public function close() : void
    {
        if ($this->stream) {
            /** @psalm-suppress InvalidPropertyAssignmentValue */
            \fclose($this->stream);
        }

        $this->stream = null;
        $this->greeting = null;
    }

    public function isClosed() : bool
    {
        return !$this->stream;
    }

    public function send(string $data) : string
    {
        if (!$this->stream || !\fwrite($this->stream, $data)) {
            throw new CommunicationFailed('Unable to write request');
        }

        $length = $this->read(PacketLength::SIZE_BYTES, 'Unable to read response length');
        $length = PacketLength::unpack($length);

        return $this->read($length, 'Unable to read response');
    }

    private function read(int $length, string $errorMessage) : string
    {
        /** @psalm-suppress PossiblyNullArgument */
        if ($data = \stream_get_contents($this->stream, $length)) {
            return $data;
        }

        /** @psalm-suppress PossiblyNullArgument */
        $meta = \stream_get_meta_data($this->stream);
        throw new CommunicationFailed($meta['timed_out'] ? 'Read timed out' : $errorMessage);
    }
}

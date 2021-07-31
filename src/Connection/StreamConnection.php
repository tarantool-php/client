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
    public const DEFAULT_TCP_URI = 'tcp://127.0.0.1:3301';

    /** @var string */
    private $uri;

    /** @var float */
    private $connectTimeout;

    /** @var float */
    private $socketTimeout;

    /** @var bool */
    private $persistent;

    /** @var resource|null */
    private $streamContext;

    /** @var resource|null */
    private $stream;

    /** @var Greeting|null */
    private $greeting;

    /**
     * @param string $uri
     */
    private function __construct($uri, float $connectTimeout, float $socketTimeout, bool $persistent, bool $tcpNoDelay)
    {
        $this->uri = $uri;
        $this->connectTimeout = $connectTimeout;
        $this->socketTimeout = $socketTimeout;
        $this->persistent = $persistent;

        if ($tcpNoDelay) {
            $this->streamContext = \stream_context_create(['socket' => ['tcp_nodelay' => true]]);
        }
    }

    public static function createTcp(string $uri = self::DEFAULT_TCP_URI, array $options = []) : self
    {
        return new self($uri,
            $options['connect_timeout'] ?? 5.0,
            $options['socket_timeout'] ?? 5.0,
            $options['persistent'] ?? false,
            $options['tcp_nodelay'] ?? false
        );
    }

    public static function createUds(string $uri, array $options = []) : self
    {
        return new self($uri,
            $options['connect_timeout'] ?? 5.0,
            $options['socket_timeout'] ?? 5.0,
            $options['persistent'] ?? false,
            false
        );
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

        $flags = $this->persistent
            ? \STREAM_CLIENT_CONNECT | \STREAM_CLIENT_PERSISTENT
            : \STREAM_CLIENT_CONNECT;

        $stream = $this->streamContext ? @\stream_socket_client(
            $this->uri,
            $errorCode,
            $errorMessage,
            $this->connectTimeout,
            $flags,
            $this->streamContext
        ) : @\stream_socket_client(
            $this->uri,
            $errorCode,
            $errorMessage,
            $this->connectTimeout,
            $flags
        );

        if (false === $stream) {
            throw ConnectionFailed::fromUriAndReason($this->uri, $errorMessage);
        }

        $socketTimeoutSeconds = (int) $this->socketTimeout;
        $socketTimeoutMicroSeconds = (int) (($this->socketTimeout - $socketTimeoutSeconds) * 1000000);
        \stream_set_timeout($stream, $socketTimeoutSeconds, $socketTimeoutMicroSeconds);

        $this->stream = $stream;

        if ($this->persistent && \ftell($stream)) {
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

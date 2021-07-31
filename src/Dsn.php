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

namespace Tarantool\Client;

final class Dsn
{
    /** @var string|null */
    private $host;

    /** @var int|null */
    private $port;

    /** @var string|null */
    private $path;

    /** @var string */
    private $connectionUri;

    /** @var string|null */
    private $username;

    /** @var string|null */
    private $password;

    /** @var bool */
    private $isTcp = false;

    /**
     * @var array<string, string>
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private $options;

    /**
     * @param string $connectionUri
     */
    private function __construct($connectionUri)
    {
        $this->connectionUri = $connectionUri;
    }

    public static function parse(string $dsn) : self
    {
        if (0 === \strpos($dsn, 'unix://') && isset($dsn[7])) {
            return self::parseUds($dsn);
        }

        if (false === $parsed = \parse_url($dsn)) {
            self::throwParseError($dsn);
        }
        if (!isset($parsed['scheme'], $parsed['host']) || 'tcp' !== $parsed['scheme']) {
            self::throwParseError($dsn);
        }
        if (isset($parsed['path']) && '/' !== $parsed['path']) {
            self::throwParseError($dsn);
        }

        $self = new self('tcp://'.$parsed['host'].':'.($parsed['port'] ?? '3301'));
        $self->host = $parsed['host'];
        $self->port = $parsed['port'] ?? 3301;
        $self->isTcp = true;

        if (isset($parsed['user'])) {
            $self->username = \rawurldecode($parsed['user']);
            $self->password = isset($parsed['pass']) ? \rawurldecode($parsed['pass']) : '';
        }

        if (isset($parsed['query'])) {
            \parse_str($parsed['query'], $self->options);
        }

        return $self;
    }

    private static function parseUds(string $dsn) : self
    {
        $parts = \explode('@', \substr($dsn, 7), 2);
        if (isset($parts[1])) {
            $parsed = \parse_url($parts[1]);
            $authority = \explode(':', $parts[0]);
        } else {
            $parsed = \parse_url($parts[0]);
        }

        if (false === $parsed) {
            self::throwParseError($dsn);
        }
        if (isset($parsed['host']) || !isset($parsed['path'])) {
            self::throwParseError($dsn);
        }

        $self = new self('unix://'.$parsed['path']);
        $self->path = \rawurldecode($parsed['path']);

        if (isset($authority)) {
            $self->username = \rawurldecode($authority[0]);
            $self->password = isset($authority[1]) ? \rawurldecode($authority[1]) : '';
        }

        if (isset($parsed['query'])) {
            \parse_str($parsed['query'], $self->options);
        }

        return $self;
    }

    public function getConnectionUri() : string
    {
        return $this->connectionUri;
    }

    public function getHost() : ?string
    {
        return $this->host;
    }

    public function getPort() : ?int
    {
        return $this->port;
    }

    public function getPath() : ?string
    {
        return $this->path;
    }

    public function getUsername() : ?string
    {
        return $this->username;
    }

    public function getPassword() : ?string
    {
        return $this->password;
    }

    public function isTcp() : bool
    {
        return $this->isTcp;
    }

    public function getString(string $name, ?string $default = null) : ?string
    {
        return $this->options[$name] ?? $default;
    }

    public function getBool(string $name, ?bool $default = null) : ?bool
    {
        if (!isset($this->options[$name])) {
            return $default;
        }

        if (null === $value = \filter_var($this->options[$name], \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE)) {
            throw new \TypeError(\sprintf('DSN option "%s" must be of type bool', $name));
        }

        return $value;
    }

    public function getInt(string $name, ?int $default = null) : ?int
    {
        if (!isset($this->options[$name])) {
            return $default;
        }

        if (false === $value = \filter_var($this->options[$name], \FILTER_VALIDATE_INT)) {
            throw new \TypeError(\sprintf('DSN option "%s" must be of type int', $name));
        }

        return $value;
    }

    public function getFloat(string $name, ?float $default = null) : ?float
    {
        if (!isset($this->options[$name])) {
            return $default;
        }

        if (false === $value = \filter_var($this->options[$name], \FILTER_VALIDATE_FLOAT)) {
            throw new \TypeError(\sprintf('DSN option "%s" must be of type float', $name));
        }

        return $value;
    }

    /**
     * @psalm-return never-returns
     */
    private static function throwParseError(string $dsn) : void
    {
        throw new \InvalidArgumentException(\sprintf('Unable to parse DSN "%s"', $dsn));
    }
}

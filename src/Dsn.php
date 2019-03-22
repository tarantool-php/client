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

namespace Tarantool\Client;

final class Dsn
{
    private const DEFAULT_TCP_PORT = '3301';

    private $connectionUri;
    private $username;
    private $password;
    private $options;

    public static function parse(string $dsn) : self
    {
        $isSocket = 0 === \strpos($dsn, 'unix://');

        if ($isSocket && $pos = \strpos($dsn, '/', 7)) {
            $dsn = \substr_replace($dsn, '.', $pos, 0);
        }

        if (false === $parts = \parse_url($dsn)) {
            throw new \InvalidArgumentException(\sprintf('Malformed DSN "%s".', $dsn));
        }

        $self = new self();

        if ($isSocket) {
            if ('' === \trim($parts['path'], '/')) {
                throw new \InvalidArgumentException(\sprintf('Malformed DSN "%s".', $dsn));
            }
            $self->connectionUri = 'unix://'.$parts['path'];
        } else {
            if (!isset($parts['scheme'], $parts['host'])) {
                throw new \InvalidArgumentException(\sprintf('Malformed DSN "%s".', $dsn));
            }
            $self->connectionUri = $parts['scheme'].'://'.$parts['host'].':'.($parts['port'] ?? self::DEFAULT_TCP_PORT);
        }

        if (isset($parts['user'])) {
            $self->username  = $parts['user'];
            $self->password = $parts['pass'] ?? '';
        }

        if (isset($parts['query'])) {
            \parse_str($parts['query'], $self->options);
        } else {
            $self->options = [];
        }

        return $self;
    }

    public function getConnectionUri() : string
    {
        return $this->connectionUri;
    }

    public function getUsername() : ?string
    {
        return $this->username;
    }

    public function getPassword() : ?string
    {
        return $this->password;
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
            throw new \InvalidArgumentException($name);
        }

        return $value;
    }

    public function getInt(string $name, ?int $default = null) : ?int
    {
        if (!isset($this->options[$name])) {
            return $default;
        }

        if (null === $value = \filter_var($this->options[$name], \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE)) {
            throw new \InvalidArgumentException($name);
        }

        return $value;
    }
}

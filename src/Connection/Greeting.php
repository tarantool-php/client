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

use Tarantool\Client\Exception\UnexpectedResponse;

final class Greeting
{
    public const SIZE_BYTES = 128;

    private $greeting;
    private $salt;
    private $serverVersion;

    private function __construct($greeting)
    {
        $this->greeting = $greeting;
    }

    public static function parse(string $greeting) : self
    {
        if (0 === \strpos($greeting, 'Tarantool')) {
            return new self($greeting);
        }

        throw new UnexpectedResponse('Unable to recognize Tarantool server.');
    }

    public function getSalt() : string
    {
        if (null !== $this->salt) {
            return $this->salt;
        }

        if (false === $salt = \base64_decode(\substr($this->greeting, 64, 44), true)) {
            throw new UnexpectedResponse('Unable to decode salt.');
        }

        $salt = \substr($salt, 0, 20);

        if (isset($salt[19])) {
            return $this->salt = $salt;
        }

        throw new UnexpectedResponse('Salt is too short.');
    }

    public function getServerVersion() : string
    {
        if (null !== $this->serverVersion) {
            return $this->serverVersion;
        }

        return $this->serverVersion = \substr($this->greeting, 10, \strspn($this->greeting, '0123456789.', 10));
    }
}

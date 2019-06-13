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

namespace Tarantool\Client;

use Tarantool\Client\Exception\InvalidGreeting;

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

        throw InvalidGreeting::invalidServerName();
    }

    public function getSalt() : string
    {
        if (null !== $this->salt) {
            return $this->salt;
        }

        if (false === $salt = \base64_decode(\substr($this->greeting, 64, 44), true)) {
            throw InvalidGreeting::invalidSalt();
        }

        $salt = \substr($salt, 0, 20);

        if (isset($salt[19])) {
            return $this->salt = $salt;
        }

        throw InvalidGreeting::invalidSalt();
    }

    public function getServerVersion() : int
    {
        if (null !== $this->serverVersion) {
            return $this->serverVersion;
        }

        [$major, $minor, $patch] = \sscanf($this->greeting, 'Tarantool %d.%d.%d');

        return $this->serverVersion = $major * 10000 + $minor * 100 + $patch;
    }
}

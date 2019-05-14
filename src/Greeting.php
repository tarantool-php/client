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

    private function __construct()
    {
    }

    public static function parse(string $greeting) : string
    {
        if (0 !== \strpos($greeting, 'Tarantool')) {
            throw InvalidGreeting::invalidServerName();
        }

        if (false === $salt = \base64_decode(\substr($greeting, 64, 44), true)) {
            throw InvalidGreeting::invalidSalt();
        }

        $salt = \substr($salt, 0, 20);

        if (isset($salt[19])) {
            return $salt;
        }

        throw InvalidGreeting::invalidSalt();
    }
}

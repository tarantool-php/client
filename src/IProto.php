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

use Tarantool\Client\Exception\InvalidGreeting;

final class IProto
{
    public const CODE = 0x00;
    public const SYNC = 0x01;
    public const SPACE_ID = 0x10;
    public const INDEX_ID = 0x11;
    public const LIMIT = 0x12;
    public const OFFSET = 0x13;
    public const ITERATOR = 0x14;
    public const KEY = 0x20;
    public const TUPLE = 0x21;
    public const FUNCTION_NAME = 0x22;
    public const USER_NAME = 0x23;
    public const SERVER_UUID = 0x24;
    public const CLUSTER_UUID = 0x25;
    public const VCLOCK = 0x26;
    public const EXPR = 0x27;
    public const OPERATIONS = 0x28;
    public const DATA = 0x30;
    public const METADATA = 0x32;
    public const ERROR = 0x31;
    public const SQL_TEXT = 0x40;
    public const SQL_BIND = 0x41;
    public const SQL_INFO = 0x42;

    public const GREETING_SIZE = 128;
    public const LENGTH_SIZE = 5;

    private function __construct()
    {
    }

    public static function parseGreeting(string $greeting) : string
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

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

final class RequestTypes
{
    public const SELECT = 1;
    public const INSERT = 2;
    public const REPLACE = 3;
    public const UPDATE = 4;
    public const DELETE = 5;
    public const AUTHENTICATE = 7;
    public const EVALUATE = 8;
    public const UPSERT = 9;
    public const CALL = 10;
    public const EXECUTE = 11;
    public const PREPARE = 13;
    public const PING = 64;

    private const ALL = [
        self::SELECT => 'select',
        self::INSERT => 'insert',
        self::REPLACE => 'replace',
        self::UPDATE => 'update',
        self::DELETE => 'delete',
        self::AUTHENTICATE => 'authenticate',
        self::EVALUATE => 'evaluate',
        self::UPSERT => 'upsert',
        self::CALL => 'call',
        self::EXECUTE => 'execute',
        self::PREPARE => 'prepare',
        self::PING => 'ping',
    ];

    public static function getName(int $type) : string
    {
        if (isset(self::ALL[$type])) {
            return self::ALL[$type];
        }

        throw new \InvalidArgumentException("Unknown request type #$type");
    }

    private function __construct()
    {
    }
}

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
    public const PING = 64;

    private function __construct()
    {
    }
}

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

/**
 * @see https://www.tarantool.io/en/doc/2.2/dev_guide/internals/box_protocol/
 */
final class Keys
{
    public const CODE = 0x00;
    public const SYNC = 0x01;
    public const SCHEMA_ID = 0x05;
    public const SPACE_ID = 0x10;
    public const INDEX_ID = 0x11;
    public const LIMIT = 0x12;
    public const OFFSET = 0x13;
    public const ITERATOR = 0x14;
    public const KEY = 0x20;
    public const TUPLE = 0x21;
    public const FUNCTION_NAME = 0x22;
    public const USER_NAME = 0x23;
    public const EXPR = 0x27;
    public const OPERATIONS = 0x28;
    public const DATA = 0x30;
    public const METADATA = 0x32;
    public const ERROR = 0x31;
    public const SQL_TEXT = 0x40;
    public const SQL_BIND = 0x41;
    public const SQL_INFO = 0x42;
    public const SQL_INFO_ROW_COUNT = 0x00;
    public const SQL_INFO_AUTO_INCREMENT_IDS = 0x01;

    private function __construct()
    {
    }
}

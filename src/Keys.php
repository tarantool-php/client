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

/**
 * @see https://www.tarantool.io/en/doc/latest/dev_guide/internals/box_protocol/
 * @see https://github.com/tarantool/tarantool/blob/master/src/box/iproto_constants.h
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
    public const ERROR_24 = 0x31;
    public const METADATA = 0x32;
    public const BIND_METADATA = 0x33;
    public const BIND_COUNT = 0x34;
    public const SQL_TEXT = 0x40;
    public const SQL_BIND = 0x41;
    public const SQL_INFO = 0x42;
    public const STMT_ID = 0x43;
    public const ERROR = 0x52;

    // Sql info map keys
    // https://github.com/tarantool/tarantool/blob/master/src/box/execute.h
    public const SQL_INFO_ROW_COUNT = 0;
    public const SQL_INFO_AUTO_INCREMENT_IDS = 1;

    // Metadata map keys
    // https://github.com/tarantool/tarantool/blob/master/src/box/iproto_constants.h
    public const METADATA_FIELD_NAME = 0;
    public const METADATA_FIELD_TYPE = 1;
    public const METADATA_FIELD_COLL = 2;
    public const METADATA_FIELD_IS_NULLABLE = 3;
    public const METADATA_FIELD_IS_AUTOINCREMENT = 4;
    public const METADATA_FIELD_SPAN = 5;

    // Error map keys
    // https://github.com/tarantool/tarantool/blob/master/src/box/mp_error.cc
    public const ERROR_STACK = 0;
    public const ERROR_TYPE = 0;
    public const ERROR_FILE = 1;
    public const ERROR_LINE = 2;
    public const ERROR_MESSAGE = 3;
    public const ERROR_NUMBER = 4;
    public const ERROR_CODE = 5;
    public const ERROR_FIELDS = 6;

    private function __construct()
    {
    }
}

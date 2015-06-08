<?php

namespace Tarantool;

use Tarantool\Exception\Exception;

abstract class IProto
{
    const CODE = 0x00;
    const SYNC = 0x01;
    const SPACE_ID = 0x10;
    const INDEX_ID = 0x11;
    const LIMIT = 0x12;
    const OFFSET = 0x13;
    const ITERATOR = 0x14;
    const KEY = 0x20;
    const TUPLE = 0x21;
    const FUNCTION_NAME = 0x22;
    const USER_NAME = 0x23;
    const SERVER_UUID = 0x24;
    const CLUSTER_UUID = 0x25;
    const VCLOCK = 0x26;
    const EXPR = 0x27;
    const DATA = 0x30;
    const ERROR = 0x31;
    const GREETING_SIZE = 128;

    public static function parseSalt($greeting)
    {
        return substr(base64_decode(substr($greeting, 64, 44)), 0, 20);
    }

    public static function parseLenght($data)
    {
        if (false === $data = unpack('Ctype/Nlength', $data)) {
            throw new Exception('Unable to parse length value.');
        }

        return $data['length'];
    }
}

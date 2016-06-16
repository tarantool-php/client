<?php

namespace Tarantool\Client\Packer;

use Tarantool\Client\Exception\Exception;

abstract class PackUtils
{
    private static $offsets = [
        // MP_UINT
        0xcd => 2,
        0xce => 4,
        0xcf => 8,

        // MP_INT
        0xd1 => 2,
        0xd2 => 4,
        0xd3 => 8,
    ];

    public static function packLength($length)
    {
        return pack('CN', 0xce, $length);
    }

    public static function unpackLength($data)
    {
        if (false === $data = @unpack('C_/Nlength', $data)) {
            throw new Exception('Unable to unpack length value.');
        }

        return $data['length'];
    }

    public static function getHeaderSize($buffer)
    {
        $offset = 0;
        $len = strlen($buffer);

        while ($offset < $len) {
            $c = ord($buffer[$offset]);

            if (self::isMap($c) && $offset) {
                break;
            }

            $offset += isset(self::$offsets[$c]) ? self::$offsets[$c] + 1 : 1;
        }

        return $offset;
    }

    private static function isMap($c)
    {
        return 0x80 === ($c & 0xf0) || 0xde === $c || 0xdf === $c;
    }
}

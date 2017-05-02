<?php

namespace Tarantool\Client\Packer;

use Tarantool\Client\Exception\Exception;

abstract class PackUtils
{
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
}

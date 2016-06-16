<?php

namespace Tarantool\Client\Tests\Adapter;

use Tarantool\Client\Exception\ConnectionException;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Tests\Integration\Utils;

class ExceptionFactory
{
    public static function create(\Exception $e)
    {
        $message = $e->getMessage();
        $code = 0;

        switch (true) {
            case 0 === strpos($message, 'Failed to connect '):
                $message = preg_replace('/^Failed to connect \[\d+?\]:(.+)$/', 'Unable to connect:\\1.', $message);

                return new ConnectionException($message);

            case 0 === strpos($message, 'Invalid primary port value: '):
                return new ConnectionException($message);

            case 0 === strpos($message, 'Query error '):
                $pos = strpos($message, ':', 12);
                $code = (int) substr($message, 12, $pos - 12);
                $message = substr($message, $pos + 2);
                break;

            case 0 === strpos($message, 'No space'):
                $message = preg_replace("/No space '([^']+?)' defined/", "Space '\\1' does not exist", $message);
                break;

            case 0 === strpos($message, 'No index'):
                // there is no way to get the space id from the error message, so use fake #0
                $message = preg_replace("/No index '([^']+?)' defined/", "No index '\\1' is defined in space #0", $message);
                break;

            case 0 === strpos($message, 'Field OP must be provided and must be'):
            case 0 === strpos($message, 'Five fields must be provided for splice at position'):
                $message = 'Unknown UPDATE operation';
                $code = 28;
                break;

            case 0 === strpos($message, 'Op must be MAP at pos'):
                if (version_compare(Utils::getTarantoolVersion(), '1.6.7', '<')) {
                    $message = 'Invalid MsgPack - expected an update operation (array)';
                    $code = 20;
                } else {
                    $message = 'Illegal parameters, update operation must be an array {op,..}, got empty array';
                    $code = 1;
                }
                break;
        }

        return new Exception($message, $code, $e);
    }
}

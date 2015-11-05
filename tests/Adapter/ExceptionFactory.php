<?php

namespace Tarantool\Tests\Adapter;

use Tarantool\Exception\ConnectionException;
use Tarantool\Exception\Exception;

class ExceptionFactory
{
    public static function create(\Exception $e)
    {
        $message = $e->getMessage();
        $code = 0;

        switch (true) {
            case 0 === strpos($message, 'Failed to connect. Code '):
            case 0 === strpos($message, 'Invalid primary port value: '):
                return new ConnectionException($message);

            case 0 === strpos($message, 'Query error '):
                $pos = strpos($message, ':', 12);
                $code = (int) substr($message, 12, $pos - 12);
                $message = substr($message, $pos + 2);
                break;

            case 0 === strpos($message, 'No space'):
                $message = preg_replace("/No space '([^']+?)' defined/", "Space '\\1' does not exist'", $message);
                break;

            case 0 === strpos($message, 'Field OP must be provided and must be'):
            case 0 === strpos($message, 'Five fields must be provided for splice at position'):
                $message = 'Unknown UPDATE operation';
                $code = 28;
                break;

            case 0 === strpos($message, 'Op must be MAP at pos'):
                $message = 'Illegal parameters, update operation must be an array {op,..}, got empty array';
                $code = 20;
                break;
        }

        return new Exception($message, $code, $e);
    }
}

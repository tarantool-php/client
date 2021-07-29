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

namespace Tarantool\Client\Tests\Unit;

final class OptionsProvider
{
    public static function provideConnectionArrayOptionsOfValidTypes() : array
    {
        return [
            ['connect_timeout', 42],
            ['connect_timeout', 42.3],
            ['socket_timeout', 42],
            ['socket_timeout', 42.3],
            ['persistent', false],
        ];
    }

    public static function provideConnectionArrayOptionsOfInvalidTypes() : array
    {
        return [
            ['connect_timeout', '42.1', 'float'],
            ['connect_timeout', false, 'float'],
            ['socket_timeout', '42.2', 'float'],
            ['socket_timeout', false, 'float'],
            ['persistent', 0, 'bool'],
            ['persistent', 'false', 'bool'],
        ];
    }

    public function provideTcpExtraConnectionArrayOptionsOfValidTypes() : array
    {
        return [
            ['tcp_nodelay', false],
        ];
    }

    public static function provideTcpExtraConnectionArrayOptionsOfInvalidTypes() : array
    {
        return [
            ['tcp_nodelay', 0, 'bool'],
            ['tcp_nodelay', 'false', 'bool'],
        ];
    }

    public static function provideClientArrayOptionsOfValidTypes() : array
    {
        return array_merge(self::provideConnectionArrayOptionsOfValidTypes(), [
            ['uri', 'foo'],
            ['username', 'foo'],
            ['password', 'bar', ['username' => 'foo']],
            ['max_retries', 42],
        ]);
    }

    public static function provideClientArrayOptionsOfInvalidTypes() : array
    {
        return array_merge(self::provideConnectionArrayOptionsOfInvalidTypes(), [
            ['uri', 0, 'string'],
            ['uri', false, 'string'],
            ['username', 0, 'string'],
            ['username', false, 'string'],
            ['password', 0, 'string', ['username' => 'foobar']],
            ['password', false, 'string', ['username' => 'foobar']],
            ['max_retries', 4.2, 'int'],
            ['max_retries', '42', 'int'],
            ['max_retries', false, 'int'],
        ]);
    }

    public static function provideClientDsnOptionsOfValidTypes() : array
    {
        return [
            ['connect_timeout=42.4'],
            ['socket_timeout=42.5'],
            ['persistent=1'],
            ['persistent=0'],
            ['persistent=on'],
            ['persistent=off'],
            ['persistent=true'],
            ['persistent=false'],
            ['persistent=yes'],
            ['persistent=no'],
            ['tcp_nodelay=1'],
            ['tcp_nodelay=0'],
            ['tcp_nodelay=on'],
            ['tcp_nodelay=off'],
            ['tcp_nodelay=true'],
            ['tcp_nodelay=false'],
            ['tcp_nodelay=yes'],
            ['tcp_nodelay=no'],
            ['username=foo'],
            ['username=foo&password=bar'],
            ['max_retries=42'],
        ];
    }

    public static function provideClientDsnOptionsOfInvalidTypes() : array
    {
        return [
            ['connect_timeout=foo', 'connect_timeout', 'float'],
            ['socket_timeout=foo', 'socket_timeout', 'float'],
            ['persistent=42.5', 'persistent', 'bool'],
            ['persistent=foo', 'persistent', 'bool'],
            ['tcp_nodelay=42.6', 'tcp_nodelay', 'bool'],
            ['max_retries=foo', 'max_retries', 'int'],
            ['max_retries=42.7', 'max_retries', 'int'],
        ];
    }
}

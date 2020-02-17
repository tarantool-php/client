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

namespace Tarantool\Client\Tests;

final class GreetingDataProvider
{
    private const DEFAULT_SERVER_VERSION = '2.2.2';
    private const SALT_SIZE_BYTES = 20;

    public static function provideGreetingsWithInvalidServerName() : iterable
    {
        return [
            [''],
            ["\n"],
            ['1'],
            ['tarantool'],
            ['Tarantoo'],
            ['Тарантул'],
            [str_repeat('2', 63)."\n"],
            [str_repeat('3', 63)."\n3"],
            [str_repeat('4', 63)."\n\n"],
            [str_repeat('5', 63)."\n\n\n"],
            [str_repeat('6', 63)."\n".str_repeat('6', 63)."\n"],
        ];
    }

    public static function provideGreetingsWithInvalidSalt() : iterable
    {
        return [
            [str_pad('Tarantool', 63, '1')."\n"],
            [str_pad('Tarantool', 63, '2')."\n2"],
            [str_pad('Tarantool', 63, '3')."\n\n"],
            [str_pad('Tarantool', 63, '4')."\n\n\n"],
            [str_pad('Tarantool', 63, '5')."\nтутсолинеттутсолинеттутсолинеттутсолинеттутсолинеттутсолинеттут\n"],
        ];
    }

    public static function provideValidGreetings() : iterable
    {
        return [
            [self::generateGreetingFromSalt('12345678901234567890'), '12345678901234567890'],
        ];
    }

    public static function generateGreeting(string $version = self::DEFAULT_SERVER_VERSION) : string
    {
        return self::generateGreetingFromSalt(self::generateSalt(), $version);
    }

    public static function generateGreetingFromSalt(string $salt, string $version = self::DEFAULT_SERVER_VERSION) : string
    {
        $uuid = self::generateUuid4();

        $greeting = str_pad("Tarantool $version (Binary) $uuid", 63, ' ')."\n";
        $greeting .= str_pad(base64_encode($salt.str_repeat('_', 12)), 63, ' ')."\n";

        return $greeting;
    }

    private static function generateSalt() : string
    {
        return random_bytes(self::SALT_SIZE_BYTES);
    }

    private static function generateUuid4() : string
    {
        $data = random_bytes(16);
        $data[6] = \chr(\ord($data[6]) & 0x0f | 0x40);
        $data[8] = \chr(\ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

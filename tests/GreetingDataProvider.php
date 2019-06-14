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
            [self::generateGreeting('12345678901234567890'), '12345678901234567890'],
        ];
    }

    public static function generateGreeting(?string $salt = null) : string
    {
        $salt = null === $salt ? substr(md5(uniqid()), 0, 20) : $salt;

        $greeting = str_pad('Tarantool 2.2.2 (Binary) 5e612e67-a9d2-4774-9709-31e44b40ffad', 63, ' ')."\n";
        $greeting .= str_pad(base64_encode($salt.str_repeat('_', 12)), 63, ' ')."\n";

        return $greeting;
    }
}

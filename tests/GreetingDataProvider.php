<?php

namespace Tarantool\Client\Tests;

class GreetingDataProvider
{
    public static function provideGreetingsWithInvalidServerName()
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

    public static function provideGreetingsWithInvalidSalt()
    {
        return [
            [str_pad('Tarantool', 63, '1')."\n"],
            [str_pad('Tarantool', 63, '2')."\n2"],
            [str_pad('Tarantool', 63, '3')."\n\n"],
            [str_pad('Tarantool', 63, '4')."\n\n\n"],
            [str_pad('Tarantool', 63, '5')."\nтутсолинеттутсолинеттутсолинеттутсолинеттутсолинеттутсолинеттут\n"],
        ];
    }

    public static function provideValidGreetings()
    {
        return [
            [self::generateGreeting('12345678901234567890'), '12345678901234567890'],
        ];
    }

    public static function generateGreeting($salt = null)
    {
        $salt = $salt ?: substr(md5(uniqid()), 0, 20);

        $greeting = str_pad('Tarantool', 63, ' ')."\n";
        $greeting .= str_pad(base64_encode($salt.str_repeat('_', 12)), 63, ' ')."\n";

        return $greeting;
    }
}

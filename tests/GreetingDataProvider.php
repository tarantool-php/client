<?php

namespace Tarantool\Tests;

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
}

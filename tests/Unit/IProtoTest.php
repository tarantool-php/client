<?php

namespace Tarantool\Tests\Unit;

use Tarantool\IProto;

class IProtoTest extends \PHPUnit_Framework_TestCase
{
    public function testParseGreeting()
    {
        $salt = '12345678901234567890';
        $greeting = base64_encode(str_repeat('x', 48).$salt.str_repeat('x', 100));

        $this->assertSame($salt, IProto::parseGreeting($greeting));
    }

    /**
     * * @dataProvider provideInvalidGreetings
     *
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Unable to parse greeting.
     */
    public function testParseGreetingThrowsException($greeting)
    {
        IProto::parseGreeting($greeting);
    }

    public function provideInvalidGreetings()
    {
        return [
            [''],
            ['1'],
            [str_repeat('2', 64)],
            [str_repeat('3', 65)],
            [str_repeat('4', 66)],
            [substr(str_repeat('тутсолинет', 13), 0, 128)],
        ];
    }
}

<?php

namespace Tarantool\Tests\Unit;

use Tarantool\IProto;

class IProtoTest extends \PHPUnit_Framework_TestCase
{
    public function testParseGreeting()
    {
        $salt = '12345678901234567890';

        $greeting = str_pad('Tarantool', 63, ' ')."\n";
        $greeting .= str_pad(base64_encode($salt.str_repeat('_', 12)), 63, ' ')."\n";

        $this->assertSame($salt, IProto::parseGreeting($greeting));
    }

    /**
     * @dataProvider Tarantool\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     *
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Invalid greeting: unable to recognize Tarantool server.
     */
    public function testParseGreetingThrowsExceptionOnInvalidServer($greeting)
    {
        IProto::parseGreeting($greeting);
    }

    /**
     * @dataProvider Tarantool\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     *
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Invalid greeting: unable to parse salt.
     */
    public function testParseGreetingThrowsExceptionOnInvalidSalt($greeting)
    {
        IProto::parseGreeting($greeting);
    }
}

<?php

namespace Tarantool\Tests\Unit;

use Tarantool\IProto;

class IProtoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider Tarantool\Tests\GreetingDataProvider::provideValidGreetings
     */
    public function testParseGreeting($greeting, $salt)
    {
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

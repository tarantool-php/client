<?php

namespace Tarantool\Client\Tests\Unit;

use Tarantool\Client\IProto;

class IProtoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider Tarantool\Client\Tests\GreetingDataProvider::provideValidGreetings
     */
    public function testParseGreeting($greeting, $salt)
    {
        $this->assertSame($salt, IProto::parseGreeting($greeting));
    }

    /**
     * @dataProvider Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     *
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Invalid greeting: unable to recognize Tarantool server.
     */
    public function testParseGreetingThrowsExceptionOnInvalidServer($greeting)
    {
        IProto::parseGreeting($greeting);
    }

    /**
     * @dataProvider Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     *
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Invalid greeting: unable to parse salt.
     */
    public function testParseGreetingThrowsExceptionOnInvalidSalt($greeting)
    {
        IProto::parseGreeting($greeting);
    }
}

<?php

namespace Tarantool\Tests\Unit;

use Tarantool\IProto;

class IProtoTest extends \PHPUnit_Framework_TestCase
{
    public function testParseSalt()
    {
        $salt = '12345678901234567890';
        $greeting = base64_encode(str_repeat('x', 48).$salt.str_repeat('x', 100));

        $this->assertSame($salt, IProto::parseSalt($greeting));
    }

    public function testParseLength()
    {
        $data = pack('CN', 0xce, 42);

        $this->assertSame(42, IProto::parseLenght($data));
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Unable to parse length value.
     */
    public function testParseLengthFromMalformedData()
    {
        IProto::parseLenght('foo');
    }
}

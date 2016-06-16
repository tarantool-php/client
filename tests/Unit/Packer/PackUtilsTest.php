<?php

namespace Tarantool\Client\Tests\Unit\Packer;

use Tarantool\Client\Packer\PackUtils;

class PackUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testPackUnpackLength()
    {
        $packed = PackUtils::packLength(42);

        $this->assertInternalType('string', $packed);
        $this->assertSame(42, PackUtils::unpackLength($packed));
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Unable to unpack length value.
     */
    public function testUnpackLengthFromMalformedData()
    {
        PackUtils::unpackLength('foo');
    }

    /**
     * @dataProvider provideHeaderData
     */
    public function testGetHeaderSize($buffer, $expectedSize)
    {
        $this->assertSame($expectedSize, PackUtils::getHeaderSize($buffer));
    }

    public function provideHeaderData()
    {
        return [
            '{}' => [hex2bin('80'), 1],
            '{1=2}' => [hex2bin('810102'), 3],
            '{1=2}{}' => [hex2bin('81010280'), 3],
            '{1=2,3=4}{}' => [hex2bin('820102030480'), 5],
            '{a=b}{}' => [hex2bin('81a161a162'), 5],
            '{a=b}{a=b}' => [hex2bin('81a161a16281a161a162'), 5],
        ];
    }
}

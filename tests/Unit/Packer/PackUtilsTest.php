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
}

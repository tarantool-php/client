<?php

namespace Tarantool\Tests\Unit\Encoder;

abstract class EncoderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Tarantool\Encoder\Encoder
     */
    private $encoder;

    protected function setUp()
    {
        $this->encoder = $this->createEncoder();
    }

    /**
     * @dataProvider provideEncodeData
     */
    public function testEncode($type, $body, $sync, $expectedHexResult)
    {
        $request = $this->getMock('Tarantool\Request\Request');

        $request->expects($this->once())->method('getType')
            ->will($this->returnValue($type));

        $request->expects($this->once())->method('getBody')
            ->will($this->returnValue($body));

        $result = $this->encoder->encode($request, $sync);
        $this->assertSame($expectedHexResult, bin2hex($result));
    }

    public function provideEncodeData()
    {
        return [
            [9, null, null, '058200090100'],
            [1, null, null, '058200010100'],
            [0, null, 1, '058200000101'],
            [0, null, 128, '0682000001cc80'],
            [0, null, 256, '0782000001cd0100'],
            [0, null, 0xffff + 1, '0982000001ce00010000'],
            [0, null, 0xffffffff + 1, '0d82000001cf0000000100000000'],
            [0, [1 => 2], 0, '088200000100810102'],
        ];
    }

    /**
     * @return \Tarantool\Encoder\Encoder
     */
    abstract protected function createEncoder();
}

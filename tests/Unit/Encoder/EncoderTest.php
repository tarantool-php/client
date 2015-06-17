<?php

namespace Tarantool\Tests\Unit\Encoder;

use Tarantool\Tests\Assert;

abstract class EncoderTest extends \PHPUnit_Framework_TestCase
{
    use Assert;

    /**
     * @var \Tarantool\Encoder\Encoder
     */
    private $encoder;

    protected function setUp()
    {
        $this->encoder = $this->createEncoder();
    }

    /**
     * @dataProvider provideEncodedData
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

    public function provideEncodedData()
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
     * @dataProvider provideDecodedData
     */
    public function testDecode($hexData, $expectedData, $expectedSync)
    {
        $response = $this->encoder->decode(hex2bin($hexData));

        $this->assertResponse($response);
        $this->assertSame($expectedData, $response->getData());
        $this->assertSame($expectedSync, $response->getSync());
    }

    public function provideDecodedData()
    {
        return [
            'ping()' => ['8200ce0000000001cf000000000000000080', null, 0],
            'evaluate("return 42")' => ['8200ce0000000001cf00000000000000008130dd000000012a', [42], 0],
            'insert(...)' => ['8200ce0000000001cf00000000000002168130dd0000000192ce000dbdb5aa666f6f5f393030353333', [[900533, 'foo_900533']], 534],
        ];
    }

    /**
     * @dataProvider provideBadlyDecodedData
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Unable to decode data.
     */
    public function testThrowExceptionOnBadlyDecodedData($data)
    {
        $this->encoder->decode($data);
    }

    public function provideBadlyDecodedData()
    {
        return [
            [null],
            ["\0"],
        ];
    }

    /**
     * @return \Tarantool\Encoder\Encoder
     */
    abstract protected function createEncoder();
}

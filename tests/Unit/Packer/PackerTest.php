<?php

namespace Tarantool\Client\Tests\Unit\Packer;

use Tarantool\Client\IProto;
use Tarantool\Client\Response;
use Tarantool\Client\Tests\Assert;
use Tarantool\Client\Tests\PhpUnitCompat;

abstract class PackerTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use PhpUnitCompat;

    /**
     * @var \Tarantool\Client\Packer\Packer
     */
    private $packer;

    protected function setUp()
    {
        $this->packer = $this->createPacker();
    }

    /**
     * @dataProvider providePackData
     */
    public function testPack($type, $body, $sync, $expectedHexResult)
    {
        $request = $this->createMock('Tarantool\Client\Request\Request');

        $request->expects($this->once())->method('getType')
            ->will($this->returnValue($type));

        $request->expects($this->once())->method('getBody')
            ->will($this->returnValue($body));

        $result = $this->packer->pack($request, $sync);
        $this->assertSame($expectedHexResult, bin2hex($result));
    }

    public function providePackData()
    {
        return [
            [9, null, null, 'ce000000058200090100'],
            [1, null, null, 'ce000000058200010100'],
            [0, null, 1, 'ce000000058200000101'],
            [0, null, 128, 'ce0000000682000001cc80'],
            [0, null, 256, 'ce0000000782000001cd0100'],
            [0, null, 0xffff + 1, 'ce0000000982000001ce00010000'],
            [0, null, 0xffffffff + 1, 'ce0000000d82000001cf0000000100000000'],
            [0, [1 => 2], 0, 'ce000000088200000100810102'],
        ];
    }

    /**
     * @dataProvider provideUnpackData
     */
    public function testUnpack($hexData, $expectedData, $expectedSync)
    {
        $response = $this->packer->unpack(hex2bin($hexData));

        $this->assertResponse($response);
        $this->assertSame($expectedData, $response->getData());
        $this->assertSame($expectedSync, $response->getSync());
    }

    public function provideUnpackData()
    {
        return [
            'ping()' => ['8200ce0000000001cf000000000000000080', null, 0],
            'evaluate("return 42")' => ['8200ce0000000001cf00000000000000008130dd000000012a', [42], 0],
            'insert(...)' => ['8200ce0000000001cf00000000000002168130dd0000000192ce000dbdb5aa666f6f5f393030353333', [[900533, 'foo_900533']], 534],
        ];
    }

    /**
     * @dataProvider provideBadUnpackData
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Unable to unpack data.
     */
    public function testThrowExceptionOnBadUnpackData($data)
    {
        $this->packer->unpack($data);
    }

    public function provideBadUnpackData()
    {
        return [
            [null],
            ["\0"],
        ];
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage foo.
     */
    public function testThrowExceptionOnIProtoError()
    {
        $header = pack('CCCnCC', 0x82, IProto::CODE, 0xcd, Response::TYPE_ERROR, IProto::SYNC, 0);
        $body = pack('C*', 0x81, IProto::ERROR, 0xa0 | 4).'foo.';

        $this->packer->unpack($header.$body);
    }

    /**
     * @return \Tarantool\Client\Packer\Packer
     */
    abstract protected function createPacker();
}

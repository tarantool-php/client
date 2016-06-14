<?php

namespace Tarantool\Tests\Unit;

use Tarantool\Client;
use Tarantool\Tests\Assert;
use Tarantool\Tests\PhpUnitCompat;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use PhpUnitCompat;

    /**
     * @var \Tarantool\Connection\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var \Tarantool\Packer\Packer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $packer;

    /**
     * @var Client
     */
    private $client;

    protected function setUp()
    {
        $this->connection = $this->createMock('Tarantool\Connection\Connection');
        $this->packer = $this->createMock('Tarantool\Packer\Packer');
        $this->client = new Client($this->connection, $this->packer);
    }

    public function testGetConnection()
    {
        $this->assertSame($this->connection, $this->client->getConnection());
    }

    /**
     * @dataProvider provideCallbackData
     */
    public function testOpenConnectionBeforeSend($methodName, array $methodArgs)
    {
        $this->connection->expects($this->once())->method('isClosed')
            ->will($this->returnValue(true));

        $this->connection->expects($this->once())->method('open');

        call_user_func_array([$this->client, $methodName], $methodArgs);
    }

    /**
     * @dataProvider provideCallbackData
     */
    public function testPackUnpackMessage($methodName, array $methodArgs)
    {
        $response = $this->createMock('Tarantool\Response');

        $this->packer->expects($this->once())->method('pack')
            ->with($this->isInstanceOf('Tarantool\Request\Request'))
            ->will($this->returnValue($this->isType('string')));

        $this->packer->expects($this->once())->method('unpack')
            ->will($this->returnValue($response));

        $response = call_user_func_array([$this->client, $methodName], $methodArgs);

        $this->assertResponse($response);
    }

    public function provideCallbackData()
    {
        return [
            ['ping', []],
            ['call', ['box.stat']],
            ['evaluate', ['return 42']],
            ['sendRequest', [$this->createMock('Tarantool\Request\Request')]],
        ];
    }
}

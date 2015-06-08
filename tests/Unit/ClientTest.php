<?php

namespace Tarantool\Tests\Unit;

use Tarantool\Client;
use Tarantool\Tests\Assert;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    use Assert;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $encoder;

    /**
     * @var Client
     */
    private $client;

    protected function setUp()
    {
        $this->connection = $this->getMock('Tarantool\Connection\Connection');
        $this->encoder = $this->getMock('Tarantool\Encoder\Encoder');
        $this->client = new Client($this->connection, $this->encoder);
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
    public function testEncodeDecodeMessage($methodName, array $methodArgs)
    {
        $response = $this->getMock('Tarantool\Response', [], [], '', false);

        $this->encoder->expects($this->once())->method('encode')
            ->with($this->isInstanceOf('Tarantool\Request\Request'))
            ->will($this->returnValue($this->isType('string')));

        $this->encoder->expects($this->once())->method('decode')
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
            ['sendRequest', [$this->getMock('Tarantool\Request\Request')]],
        ];
    }
}

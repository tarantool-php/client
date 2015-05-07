<?php

namespace Tarantool\Tests\Unit;

use Tarantool\Client;
use Tarantool\Tests\Assert;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    use Assert;

    private $connection;
    private $encoder;
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
     * @dataProvider provideMethodCallData
     */
    public function testMethodCall($methodName, array $methodArgs)
    {
        $response = $this->getMock('Tarantool\Response', [], [], '', false);
        $response->expects($this->any())->method('getData')
            ->will($this->returnValue([[1, 2, 3]]));

        $this->encoder->expects($this->atLeastOnce())->method('encode')
            ->with($this->isInstanceOf('Tarantool\Request\Request'))
            ->will($this->returnValue($this->isType('string')));

        $this->encoder->expects($this->atLeastOnce())->method('decode')
            ->will($this->returnValue($response));

        $response = call_user_func_array([$this->client, $methodName], $methodArgs);

        $this->assertResponse($response);
    }

    public function provideMethodCallData()
    {
        return [
            ['ping', []],
            ['select', ['foo', [42]]],
            ['insert', ['foo', [1]]],
            ['replace', ['foo', [1, 2]]],
            ['update', ['foo', 1, [['+', 1, 2]]]],
            ['delete', ['foo', [1]]],
            ['call', ['foo']],
            ['evaluate', ['foo']],
        ];
    }
}

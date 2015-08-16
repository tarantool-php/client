<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Tests\Assert;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    /**
     * @dataProvider providerCallData
     */
    public function testCall(array $args, $result)
    {
        $response = 1 !== count($args)
            ? call_user_func_array([self::$client, 'call'], $args)
            : self::$client->call($args[0]);

        $this->assertResponse($response);
        $this->assertSame($result, $response->getData());
    }

    public function providerCallData()
    {
        return [
            [['func_foo'], [[['foo' => 'foo', 'bar' => 42]]]],
            [['func_sum', [42, -24]], [[18]]],
            [['func_arg', [ [[42]] ]], [[ 42 ]]],
            [['func_arg', [ [42] ]], [[ 42 ]]],
        ];
    }

    /**
     * @dataProvider providerEvaluateData
     */
    public function testEvaluate(array $args, $result)
    {
        $response = 1 !== count($args)
            ? call_user_func_array([self::$client, 'evaluate'], $args)
            : self::$client->evaluate($args[0]);

        $this->assertResponse($response);
        $this->assertSame($result, $response->getData());
    }

    public function providerEvaluateData()
    {
        return [
            [['return func_foo()'], [['foo' => 'foo', 'bar' => 42]]],
            [['return func_sum(...)', [42, -24]], [18]],
            [['return func_arg(...)', [ [[42]] ]], [ [[42]] ]],
            [['return func_arg(...)', [ [42] ]], [ [42] ]],
        ];
    }

    public function testCacheSpace()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSpaces();
        self::$client->getSpace('space_conn')->select();
        self::$client->getSpace('space_conn')->select();

        $this->assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testFlushSpaces()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSpaces();
        self::$client->getSpace('space_conn')->select();
        self::$client->flushSpaces();
        self::$client->getSpace('space_conn')->select();

        $this->assertSame(4, self::getTotalSelectCalls() - $total);
    }
}

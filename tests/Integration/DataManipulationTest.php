<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Exception\Exception;
use Tarantool\Tests\Assert;

class DataManipulationTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    /**
     * @dataProvider provideSelectData
     */
    public function testSelect($expectedCount, array $args)
    {
        array_unshift($args, 'space_data');
        $response = call_user_func_array([self::$client, 'select'], $args);

        $this->assertResponse($response);
        $this->assertCount($expectedCount, $response->getData());
    }

    public function provideSelectData()
    {
        return [
            [100, []],
            [20, [[1], 'secondary']],
            [20, [[2], 'secondary']],
            [20, [[3], 'secondary']],
            [20, [[4], 'secondary']],
            [20, [[0], 'secondary']],
            [0, [[3, 'tuple_95'], 'secondary']],
            [1, [[3, 'tuple_94'], 'secondary']],
            [1, [[1]]],
            [10, [[1], 'secondary', 10]],
            [10, [[1], 'secondary', 11, 10]],
            [9, [[1], 'secondary', 9, 10]],
            [10, [[1], 'secondary', 10, 10]],
        ];
    }

    public function testSelectEmpty()
    {
        $response = self::$client->select('space_empty');

        $this->assertResponse($response);
        $this->assertEmpty($response->getData());
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Space 'non_existing_space' does not exist
     */
    public function testSelectFromNonExistingSpaceByName()
    {
        self::$client->select('non_existing_space');
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Space '123456' does not exist
     */
    public function testSelectFromNonExistingSpaceById()
    {
        self::$client->select(123456);
    }

    /**
     * @dataProvider provideInsertData
     */
    public function testInsert($space, $values)
    {
        $response = self::$client->insert($space, $values);

        $this->assertResponse($response);
        $this->assertSame([$values], $response->getData());
    }

    public function provideInsertData()
    {
        return [
            ['space_str', ['']],
            ['space_str', ['foo']],
            ['space_str', ['null', null, null]],
            ['space_str', ['int', 42, -42]],
            ['space_str', ['float', 4.2, -4.2]],
            ['space_str', ['array', ['foo' => 'bar']]],
            ['space_num', [42]],
        ];
    }

    /**
     * @dataProvider provideInsertDataWithMismatchedTypes
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessageRegExp /Tuple field 0 type does not match one required by operation: expected (NUM|STR)/
     * @expectedExceptionCode 23
     */
    public function testInsertTypeMismatchedValues($space, $values)
    {
        self::$client->insert($space, $values);
    }

    public function provideInsertDataWithMismatchedTypes()
    {
        return [
            ['space_str', [null]],
            ['space_str', [42]],
            ['space_str', [[]]],
            ['space_num', [null]],
            ['space_num', [-42]],
            ['space_num', [4.2]],
            ['space_num', [[]]],
        ];
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Duplicate key exists in unique index 'primary' in space 'space_foobar'
     * @expectedExceptionCode 3
     */
    public function testInsertDuplicateKey()
    {
        self::$client->insert('space_foobar', [1, 'baz']);
    }

    public function testReplace()
    {
        $response = self::$client->replace('space_foobar', [1, 'baz']);

        $this->assertResponse($response);
        $this->assertSame([[1, 'baz']], $response->getData());
    }

    public function testDelete()
    {
        $response = self::$client->delete('space_foobar', [2]);

        $this->assertResponse($response);
        $this->assertSame([[2, 'bar']], $response->getData());
    }

    /**
     * @dataProvider provideUpdateData
     */
    public function testUpdate(array $operations, $result)
    {
        $response = self::$client->update('space_data', 1, $operations);

        $this->assertResponse($response);
        $this->assertSame([$result], $response->getData());
    }

    public function provideUpdateData()
    {
        return [
            [
                [['+', 1, 16], ['=', 3, 98], ['=', 4, 0x11111]],
                [1, 18, 'tuple_1', 98, 0x11111],
            ],
            [
                [['-', 3, 10], ['&', 4, 0x10101]],
                [1, 18, 'tuple_1', 88, 0x10101],
            ],
            [
                [['^', 4, 0x11100]],
                [1, 18, 'tuple_1', 88, 0x01001],
            ],
            [
                [['^', 4, 0x00010]],
                [1, 18, 'tuple_1', 88, 0x01011],
            ],
            [
                [[':', 2, 2, 1, 'uup']],
                [1, 18, 'tuuuple_1', 88, 0x01011],
            ],
        ];
    }

    public function testUpdateByNonExistingKey()
    {
        $response = self::$client->update('space_foobar', 42, ['qux']);

        $this->assertResponse($response);
        $this->assertSame([], $response->getData());
    }

    /**
     * @dataProvider providerInvalidUpdateData
     */
    public function testUpdateUsingInvalidArgs(array $operation, $errorMessage, $errorCode)
    {
        try {
            self::$client->update('space_data', 1, [$operation]);
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame($errorMessage, $e->getMessage());
            $this->assertSame($errorCode, $e->getCode());
        }
    }

    public function providerInvalidUpdateData()
    {
        return [
            [[], 'Invalid MsgPack - expected an update operation (array)', 20],
            [['+', 2, 1], "Argument type in operation '+' on field 2 does not match field type: expected a NUMBER", 26],
            [['', 2, 2], 'Unknown UPDATE operation', 28],
            [['bad_op', 2, 2], 'Unknown UPDATE operation', 28],
            [[':', 2, 2, 2, '', 'extra'], 'Unknown UPDATE operation', 28],
        ];
    }

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
            [['func_arg', [42]], [[42]]],
            [['func_arg', [-42]], [[-42]]],
            [['func_arg', [null]], [[null]]],
            [['func_arg', ['foo']], [['foo']]],
            [['func_arg', [[1, 2, 3]]], [[1, 2, 3]]],
            [['func_mixed'], [
                [true],
                [[
                    's' => [1, 1428578535],
                    'u' => 1428578535,
                    'v' => [],
                    'c' => [
                        2 => [1, 1428578535],
                        106 => [1, 1428578535],
                    ],
                    'pc' => [
                        2 => [1, 1428578535, 9243],
                        106 => [1, 1428578535, 9243],
                    ],
                ]],
                [true]
            ]],
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
            [['return func_arg(...)', [42]], [42]],
            [['return func_arg(...)', [-42]], [-42]],
            [['return func_arg(...)', [null]], [null]],
            [['return func_arg(...)', ['foo']], ['foo']],
            [['return func_arg(...)', [[1, 2, 3]]], [[1, 2, 3]]],
            [['return func_mixed()'], [
                true,
                [
                    's' => [1, 1428578535],
                    'u' => 1428578535,
                    'v' => [],
                    'c' => [
                        2 => [1, 1428578535],
                        106 => [1, 1428578535],
                    ],
                    'pc' => [
                        2 => [1, 1428578535, 9243],
                        106 => [1, 1428578535, 9243],
                    ],
                ],
                true
            ]],
        ];
    }
}

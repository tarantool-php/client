<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Exception\Exception;
use Tarantool\Schema\Space;
use Tarantool\Tests\Assert;

class SpaceTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    /**
     * @dataProvider provideSelectData
     */
    public function testSelect($expectedCount, array $args)
    {
        $space = self::$client->getSpace('space_data');
        $response = call_user_func_array([$space, 'select'], $args);

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
        $response = self::$client->getSpace('space_empty')->select();

        $this->assertResponse($response);
        $this->assertEmpty($response->getData());
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Space 'non_existing_space' does not exist
     */
    public function testSelectFromNonExistingSpaceByName()
    {
        self::$client->getSpace('non_existing_space')->select();
    }

    /**
     * @expectedException \Tarantool\Exception\Exception
     * @expectedExceptionMessage Space '123456' does not exist
     */
    public function testSelectFromNonExistingSpaceById()
    {
        self::$client->getSpace(123456)->select();
    }

    /**
     * @dataProvider provideInsertData
     */
    public function testInsert($space, $values)
    {
        $space = self::$client->getSpace($space);
        $response = $space->insert($values);

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
     * @expectedExceptionMessageRegExp /Tuple field 1 type does not match one required by operation: expected (NUM|STR)/
     * @expectedExceptionCode 23
     */
    public function testInsertTypeMismatchedValues($space, $values)
    {
        $space = self::$client->getSpace($space);
        $space->insert($values);
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
        $space = self::$client->getSpace('space_foobar');
        $space->insert([1, 'baz']);
    }

    public function testReplace()
    {
        $space = self::$client->getSpace('space_foobar');
        $response = $space->replace([1, 'baz']);

        $this->assertResponse($response);
        $this->assertSame([[1, 'baz']], $response->getData());
    }

    public function testDelete()
    {
        $space = self::$client->getSpace('space_foobar');
        $response = $space->delete([2]);

        $this->assertResponse($response);
        $this->assertSame([[2, 'bar']], $response->getData());
    }

    /**
     * @dataProvider provideUpdateData
     */
    public function testUpdate(array $operations, $result)
    {
        $space = self::$client->getSpace('space_data');
        $response = $space->update(1, $operations);

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
        $space = self::$client->getSpace('space_foobar');
        $response = $space->update(42, [['=', 2, 'qux']]);

        $this->assertResponse($response);
        $this->assertSame([], $response->getData());
    }

    /**
     * @dataProvider providerInvalidUpdateData
     */
    public function testUpdateUsingInvalidArgs(array $operation, $errorMessage, $errorCode)
    {
        $space = self::$client->getSpace('space_data');

        try {
            $space->update(1, [$operation]);
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

    public function testCacheIndex()
    {
        $space = self::$client->getSpace(Space::INDEX);

        $total = self::getTotalSelectCalls();

        $space->flushIndexes();
        $space->select([], 'name');
        $space->select([], 'name');

        $this->assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testFlushIndexes()
    {
        $space = self::$client->getSpace(Space::INDEX);

        $total = self::getTotalSelectCalls();

        $space->flushIndexes();
        $space->select([], 'name');
        $space->flushIndexes();
        $space->select([], 'name');

        $this->assertSame(4, self::getTotalSelectCalls() - $total);
    }
}

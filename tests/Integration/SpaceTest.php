<?php

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Schema\Space;
use Tarantool\Client\Tests\Assert;

class SpaceTest extends \PHPUnit_Framework_TestCase
{
    use Assert;
    use Client;

    /**
     * @beforeClass
     */
    public static function createFixtures()
    {
        $client = ClientBuilder::createFromEnv()->build();
        $client->evaluate('create_fixtures()');
    }

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
            [20, [[1], 'secondary', null, null, 1]],
        ];
    }

    public function testSelectEmpty()
    {
        $response = self::$client->getSpace('space_empty')->select();

        $this->assertResponse($response);
        $this->assertEmpty($response->getData());
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
     * @expectedException \Tarantool\Client\Exception\Exception
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
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Duplicate key exists in unique index 'primary' in space 'space_misc'
     * @expectedExceptionCode 3
     */
    public function testInsertDuplicateKey()
    {
        $space = self::$client->getSpace('space_misc');
        $space->insert([1, 'bazqux']);
    }

    public function testReplace()
    {
        $space = self::$client->getSpace('space_misc');
        $response = $space->replace([2, 'replaced']);

        $this->assertResponse($response);
        $this->assertSame([[2, 'replaced']], $response->getData());
    }

    public function testDelete()
    {
        $space = self::$client->getSpace('space_misc');
        $response = $space->delete([3]);

        $this->assertResponse($response);
        $this->assertSame([[3, 'delete_me_1']], $response->getData());
    }

    public function testDeleteWithIndexId()
    {
        $space = self::$client->getSpace('space_misc');
        $response = $space->delete(['delete_me_2'], 1);

        $this->assertResponse($response);
        $this->assertSame([[4, 'delete_me_2']], $response->getData());
    }

    public function testDeleteWithIndexName()
    {
        $space = self::$client->getSpace('space_misc');
        $response = $space->delete(['delete_me_3'], 'secondary');

        $this->assertResponse($response);
        $this->assertSame([[5, 'delete_me_3']], $response->getData());
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
        $space = self::$client->getSpace('space_misc');
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
        $data = [
            [['+', 2, 1], "Argument type in operation '+' on field 2 does not match field type: expected a NUMBER", 26],
            [['', 2, 2], 'Unknown UPDATE operation', 28],
            [['bad_op', 2, 2], 'Unknown UPDATE operation', 28],
            [[':', 2, 2, 2, '', 'extra'], 'Unknown UPDATE operation', 28],
        ];

        if (version_compare(Utils::getTarantoolVersion(), '1.6.7', '<')) {
            $data[] = [[], 'Invalid MsgPack - expected an update operation (array)', 20];
        } else {
            $data[] = [[], 'Illegal parameters, update operation must be an array {op,..}, got empty array', 1];
        }

        return $data;
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Space 'non_existing_space' does not exist
     */
    public function testReferenceNonExistingSpaceByName()
    {
        self::$client->getSpace('non_existing_space')->select();
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessage Space '123456' does not exist
     */
    public function testReferenceNonExistingSpaceById()
    {
        self::$client->getSpace(123456)->select();
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessageRegExp /No index 'non_existing_index' is defined in space #\d+?/
     */
    public function testReferenceNonExistingIndexByName()
    {
        self::$client->getSpace('space_misc')->select([1], 'non_existing_index');
    }

    /**
     * @expectedException \Tarantool\Client\Exception\Exception
     * @expectedExceptionMessageRegExp /No index #123456 is defined in space 'space_misc'/
     */
    public function testReferenceNonExistingIndexById()
    {
        self::$client->getSpace('space_misc')->select([1], 123456);
    }

    public function testCacheIndex()
    {
        $space = self::$client->getSpace(Space::VINDEX);

        $total = Utils::getTotalSelectCalls();

        $space->flushIndexes();
        $space->select([], 'name');
        $space->select([], 'name');

        $this->assertSame(3, Utils::getTotalSelectCalls() - $total);
    }

    public function testFlushIndexes()
    {
        $space = self::$client->getSpace(Space::VINDEX);

        $total = Utils::getTotalSelectCalls();

        $space->flushIndexes();
        $space->select([], 'name');
        $space->flushIndexes();
        $space->select([], 'name');

        $this->assertSame(4, Utils::getTotalSelectCalls() - $total);
    }
}

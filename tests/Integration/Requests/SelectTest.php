<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\TestCase;

final class SelectTest extends TestCase
{
    /**
     * @dataProvider provideSelectData
     *
     * @eval space = create_space('request_select')
     * @eval space:create_index('primary', {type = 'tree', unique = true, parts = {1, 'unsigned'}})
     * @eval space:create_index('secondary', {type = 'tree', unique = false, parts = {2, 'unsigned', 3, 'str'}})
     * @eval for i = 1, 100 do space:replace{i, i * 2 % 5, 'tuple_' .. i} end
     */
    public function testSelect(int $expectedCount, Criteria $criteria) : void
    {
        $space = $this->client->getSpace('request_select');
        $result = $space->select($criteria);

        self::assertCount($expectedCount, $result);
    }

    public function provideSelectData() : iterable
    {
        return [
            [100, Criteria::key([])],
            [20, Criteria::key([1])->andIndex('secondary')],
            [20, Criteria::key([2])->andIndex('secondary')],
            [20, Criteria::key([3])->andIndex('secondary')],
            [20, Criteria::key([4])->andIndex('secondary')],
            [20, Criteria::key([0])->andIndex('secondary')],
            [0, Criteria::key([3, 'tuple_95'])->andIndex('secondary')],
            [1, Criteria::key([3, 'tuple_94'])->andIndex('secondary')],
            [1, Criteria::key([1])],
            [10, Criteria::key([1])->andIndex('secondary')->andLimit(10)],
            [10, Criteria::key([1])->andIndex('secondary')->andLimit(11)->andOffset(10)],
            [9, Criteria::key([1])->andIndex('secondary')->andLimit(9)->andOffset(10)],
            [10, Criteria::key([1])->andIndex('secondary')->andLimit(10)->andOffset(10)],
            [20, Criteria::key([1])->andIndex('secondary')->andLimit(100500)->andReqIterator()],
        ];
    }

    /**
     * @eval create_space('request_select'):create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
     */
    public function testSelectEmpty() : void
    {
        $space = $this->client->getSpace('request_select');

        self::assertEmpty($space->select(Criteria::key([])));
    }

    /**
     * @eval create_space('request_select'):create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testSelectWithNonExistingIndexName() : void
    {
        $space = $this->client->getSpace('request_select');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("No index 'non_existing_index' is defined in space #".$space->getId());

        $space->select(Criteria::key([1])->andIndex('non_existing_index'));
    }

    /**
     * @eval create_space('request_select'):create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testSelectWithNonExistingIndexId() : void
    {
        $space = $this->client->getSpace('request_select');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("No index #123456 is defined in space 'request_select'");

        $space->select(Criteria::key([1])->andIndex(123456));
    }
}

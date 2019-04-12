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

/**
 * @eval create_fixtures()
 */
final class SelectTest extends TestCase
{
    /**
     * @dataProvider provideSelectData
     */
    public function testSelect(int $expectedCount, Criteria $criteria) : void
    {
        $space = $this->client->getSpace('space_data');
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

    public function testSelectEmpty() : void
    {
        $space = $this->client->getSpace('space_empty');

        self::assertEmpty($space->select(Criteria::key([])));
    }

    public function testSelectWithNonExistingIndexName() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("No index 'non_existing_index' is defined in space #".$space->getId());

        $space->select(Criteria::key([1])->andIndex('non_existing_index'));
    }

    public function testSelectWithNonExistingIndexId() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("No index #123456 is defined in space 'space_misc'");

        $space->select(Criteria::key([1])->andIndex(123456));
    }
}

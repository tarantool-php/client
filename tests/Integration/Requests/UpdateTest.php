<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @lua space = create_space('request_update')
 * @lua space:create_index('primary', {type = 'tree', unique = true, parts = {1, 'unsigned'}})
 * @lua space:create_index('secondary', {type = 'tree', unique = false, parts = {2, 'unsigned', 3, 'str'}})
 * @lua space:replace{1, 2, 'tuple_1'}
 * @lua space:replace{2, 4, 'tuple_2'}
 */
final class UpdateTest extends TestCase
{
    /**
     * @dataProvider provideUpdateData
     */
    public function testUpdate(Operations $operations, array $expectedResult) : void
    {
        $space = $this->client->getSpace('request_update');
        $result = $space->update([1], $operations);

        self::assertSame($expectedResult, $result);
    }

    public function provideUpdateData() : iterable
    {
        return [
            [
                Operations::add(1, 16)->andSet(3, 98)->andSet(4, 0x11111),
                [[1, 18, 'tuple_1', 98, 0x11111]],
            ],
            [
                Operations::subtract(3, 10)->andBitwiseAnd(4, 0x10101),
                [[1, 18, 'tuple_1', 88, 0x10101]],
            ],
            [
                Operations::bitwiseXor(4, 0x11100),
                [[1, 18, 'tuple_1', 88, 0x01001]],
            ],
            [
                Operations::splice(2, 2, 1, 'uup'),
                [[1, 18, 'tuuuple_1', 88, 0x01001]],
            ],
        ];
    }

    public function testUpdateWithIndexName() : void
    {
        $space = $this->client->getSpace('request_update');

        self::assertSame([2, 4, 'tuple_2'], $space->select(Criteria::key([2]))[0]);

        $result = $space->update([2], Operations::splice(2, 0, 1, 'T'), 'primary');

        self::assertSame([[2, 4, 'Tuple_2']], $result);
    }

    public function testUpdateWithNonExistingIndexName() : void
    {
        $space = $this->client->getSpace('request_update');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("No index 'non_existing_index' is defined in space #".$space->getId());

        $space->update([2], Operations::splice(2, 0, 1, 'T'), 'non_existing_index');
    }

    public function testUpdateByNonExistingKey() : void
    {
        $space = $this->client->getSpace('request_update');
        $result = $space->update([42], Operations::set(2, 'qux'));

        self::assertSame([], $result);
    }

    public function testUpdateByEmptyKey() : void
    {
        $space = $this->client->getSpace('request_update');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(19); // ER_EXACT_MATCH

        $space->update([], Operations::set(2, 'qux'));
    }
}

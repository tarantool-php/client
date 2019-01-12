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

use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Schema\IteratorTypes;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval create_fixtures()
 */
final class SelectTest extends TestCase
{
    /**
     * @dataProvider provideSelectData
     */
    public function testSelect(int $expectedCount, array $args) : void
    {
        $space = $this->client->getSpace('space_data');
        $response = $space->select(...$args);

        self::assertCount($expectedCount, $response->getData());
    }

    public function provideSelectData() : iterable
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
            [20, [[1], 'secondary', 100500, 0, IteratorTypes::REQ]],
        ];
    }

    public function testSelectEmpty() : void
    {
        $response = $this->client->getSpace('space_empty')->select();

        self::assertEmpty($response->getData());
    }

    public function testSelectWithNonExistingName() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No index 'non_existing_index' is defined in space #".$space->getId());

        $space->select([1], 'non_existing_index');
    }

    public function testSelectWithNonExistingId() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No index #123456 is defined in space 'space_misc'");

        $space->select([1], 123456);
    }
}

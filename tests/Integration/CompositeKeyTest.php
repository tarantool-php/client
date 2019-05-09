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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;

/**
 * @eval space = create_space('composite_key')
 * @eval space:create_index('primary', {type = 'tree', unique = true, parts = {1, 'unsigned', 2, 'unsigned'}})
 * @eval space:insert{2016, 10, 1}
 * @eval space:insert{2016, 11, 0}
 */
final class CompositeKeyTest extends TestCase
{
    public function testSelectByCompositeKey() : void
    {
        $space = $this->client->getSpace('composite_key');

        self::assertSame(1, $space->select(Criteria::key([2016, 10]))[0][2]);
        self::assertSame(0, $space->select(Criteria::key([2016, 11]))[0][2]);
    }

    public function testUpdateByCompositeKey() : void
    {
        $space = $this->client->getSpace('composite_key');

        $space->update([2016, 10], Operations::set(2, 0));

        self::assertSame(0, $space->select(Criteria::key([2016, 10]))[0][2]);
    }

    public function testDeleteByCompositeKey() : void
    {
        $space = $this->client->getSpace('composite_key');

        $space->delete([2016, 11]);

        self::assertCount(0, $space->select(Criteria::key([2016, 11])));
        self::assertCount(1, $space->select(Criteria::key([2016])));
    }
}

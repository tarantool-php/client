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

namespace Tarantool\Client\Tests\Integration;

/**
 * @eval create_fixtures()
 */
final class CompositeKeyTest extends TestCase
{
    public function testSelectByCompositeKey() : void
    {
        $space = $this->client->getSpace('space_composite');

        self::assertSame(1, $space->select([2016, 10])->getData()[0][2]);
        self::assertSame(0, $space->select([2016, 11])->getData()[0][2]);
    }

    public function testUpdateByCompositeKey() : void
    {
        $space = $this->client->getSpace('space_composite');

        $space->update([2016, 10], [['=', 2, 0]]);

        self::assertSame(0, $space->select([2016, 10])->getData()[0][2]);
    }

    public function testDeleteByCompositeKey() : void
    {
        $space = $this->client->getSpace('space_composite');

        $space->delete([2016, 11]);

        self::assertCount(0, $space->select([2016, 11])->getData());
        self::assertCount(1, $space->select([2016])->getData());
    }
}

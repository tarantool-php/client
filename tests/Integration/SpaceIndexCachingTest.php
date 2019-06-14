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
use Tarantool\Client\Schema\Space;

final class SpaceIndexCachingTest extends TestCase
{
    public function testCacheIndex() : void
    {
        $space = $this->client->getSpaceById(Space::VINDEX_ID);
        $total = self::getTotalCalls(self::STAT_REQUEST_SELECT);

        $space->flushIndexes();
        $space->select(Criteria::index('name'));
        $space->select(Criteria::index('name'));

        self::assertSame(3, self::getTotalCalls(self::STAT_REQUEST_SELECT) - $total);
    }

    public function testFlushIndexes() : void
    {
        $space = $this->client->getSpaceById(Space::VINDEX_ID);
        $total = self::getTotalCalls(self::STAT_REQUEST_SELECT);

        $space->flushIndexes();
        $space->select(Criteria::index('name'));
        $space->flushIndexes();
        $space->select(Criteria::index('name'));

        self::assertSame(4, self::getTotalCalls(self::STAT_REQUEST_SELECT) - $total);
    }
}

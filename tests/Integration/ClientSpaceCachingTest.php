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

/**
 * @lua create_space('test_space_caching'):create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
 */
final class ClientSpaceCachingTest extends TestCase
{
    public function testCacheSpace() : void
    {
        $this->client->flushSpaces();

        $this->expectSelectRequestToBeCalled(3);

        $this->client->getSpace('test_space_caching')->select(Criteria::key([]));
        $this->client->getSpace('test_space_caching')->select(Criteria::key([]));
    }

    public function testFlushSpaces() : void
    {
        $this->client->flushSpaces();

        $this->expectSelectRequestToBeCalled(4);

        $this->client->getSpace('test_space_caching')->select(Criteria::key([]));
        $this->client->flushSpaces();
        $this->client->getSpace('test_space_caching')->select(Criteria::key([]));
    }
}

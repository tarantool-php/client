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
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @lua space = create_space('request_replace')
 * @lua space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
 * @lua space:create_index('secondary', {type = 'tree', parts = {2, 'str'}})
 * @lua space:insert{2, 'replace_me'}
 */
final class ReplaceTest extends TestCase
{
    public function testReplace() : void
    {
        $space = $this->client->getSpace('request_replace');

        self::assertSame([[2, 'replace_me']], $space->select(Criteria::key([2])));
        self::assertSame([[2, 'replaced']], $space->replace([2, 'replaced']));
    }

    public function testReplaceEmptyTuple() : void
    {
        $space = $this->client->getSpace('request_replace');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(39); // ER_FIELD_MISSING

        $space->replace([]);
    }
}

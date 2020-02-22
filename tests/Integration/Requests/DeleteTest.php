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
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval space = create_space('request_delete')
 * @eval space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
 * @eval space:create_index('secondary', {type = 'tree', parts = {2, 'str'}})
 * @eval space:insert{3, 'delete_me_1'}
 * @eval space:insert{4, 'delete_me_2'}
 * @eval space:insert{5, 'delete_me_3'}
 */
final class DeleteTest extends TestCase
{
    public function testDelete() : void
    {
        $space = $this->client->getSpace('request_delete');
        $result = $space->delete([3]);

        self::assertSame([[3, 'delete_me_1']], $result);
    }

    public function testDeleteWithIndexId() : void
    {
        $space = $this->client->getSpace('request_delete');
        $result = $space->delete(['delete_me_2'], 1);

        self::assertSame([[4, 'delete_me_2']], $result);
    }

    public function testDeleteWithIndexName() : void
    {
        $space = $this->client->getSpace('request_delete');
        $result = $space->delete(['delete_me_3'], 'secondary');

        self::assertSame([[5, 'delete_me_3']], $result);
    }

    public function testDeleteByEmptyKey() : void
    {
        $space = $this->client->getSpace('request_delete');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(19); // ER_EXACT_MATCH

        $space->delete([]);
    }
}

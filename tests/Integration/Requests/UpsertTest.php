<?php

/**
 * This file is part of the tarantool/client package.
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
 * @lua space = create_space('request_upsert')
 * @lua space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
 */
final class UpsertTest extends TestCase
{
    public function testUpsert() : void
    {
        $space = $this->client->getSpace('request_upsert');

        $key = 10;
        $values = [$key, 'upserted'];
        $operations = Operations::splice(1, 0, 1, 'U');
        $updatedValues = [$key, 'Upserted'];

        $space->upsert($values, $operations);
        self::assertSame([$values], $space->select(Criteria::key([$key])));

        $space->upsert($values, $operations);
        self::assertSame([$updatedValues], $space->select(Criteria::key([$key])));
    }

    public function testUpsertEmptyTuple() : void
    {
        $space = $this->client->getSpace('request_upsert');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(39); // ER_FIELD_MISSING

        $space->upsert([], Operations::set(1, 'Foo'));
    }
}

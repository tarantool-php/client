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

use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval create_fixtures()
 */
final class UpsertTest extends TestCase
{
    public function testUpsert() : void
    {
        $space = $this->client->getSpace('space_misc');

        $key = 10;
        $values = [$key, 'upserted'];
        $operations = Operations::splice(1, 0, 1, 'U');
        $updatedValues = [$key, 'Upserted'];

        $space->upsert($values, $operations);
        self::assertSame([$values], $space->select(Criteria::key([$key])));

        $space->upsert($values, $operations);
        self::assertSame([$updatedValues], $space->select(Criteria::key([$key])));
    }
}

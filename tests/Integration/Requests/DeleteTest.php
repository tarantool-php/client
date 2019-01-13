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

use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval create_fixtures()
 */
final class DeleteTest extends TestCase
{
    public function testDelete() : void
    {
        $space = $this->client->getSpace('space_misc');
        $result = $space->delete([3]);

        self::assertSame([[3, 'delete_me_1']], $result);
    }

    public function testDeleteWithIndexId() : void
    {
        $space = $this->client->getSpace('space_misc');
        $result = $space->delete(['delete_me_2'], 1);

        self::assertSame([[4, 'delete_me_2']], $result);
    }

    public function testDeleteWithIndexName() : void
    {
        $space = $this->client->getSpace('space_misc');
        $result = $space->delete(['delete_me_3'], 'secondary');

        self::assertSame([[5, 'delete_me_3']], $result);
    }
}

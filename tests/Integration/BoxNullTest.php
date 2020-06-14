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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Schema\Criteria;

final class BoxNullTest extends TestCase
{
    /**
     * @lua format = {}
     * @lua format[1] = {name = 'foo', type = 'unsigned'}
     * @lua format[2] = {name = 'bar', type = 'map', is_nullable = true}
     * @lua format[3] = {name = 'baz', type = 'unsigned', is_nullable = true}
     * @lua space = create_space('box_null', {format = format})
     * @lua space:create_index('pk')
     * @lua space:insert{1, {a = 1}}
     * @lua space:insert{2, {b = 2}, box.NULL}
     * @lua space:insert{3, box.NULL, 300}
     */
    public function testNull() : void
    {
        $space = $this->client->getSpace('box_null');

        $space->insert([4, ['d' => 4], 400]);
        $space->insert([5, null, 500]);
        $space->insert([6, ['f' => 6], null]);
        $space->insert([7, []]);
        $space->insert([8, [], 800]);
        $space->insert([9]);

        self::assertSame([
            [1, ['a' => 1]],
            [2, ['b' => 2], null],
            [3, null, 300],
            [4, ['d' => 4], 400],
            [5, null, 500],
            [6, ['f' => 6], null],
            [7, []],
            [8, [], 800],
            [9],
        ], $space->select(Criteria::key([])));
    }
}

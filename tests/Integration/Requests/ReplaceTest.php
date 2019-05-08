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
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval space = create_space('request_replace')
 * @eval space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
 * @eval space:create_index('secondary', {type = 'tree', parts = {2, 'str'}})
 * @eval space:insert{2, 'replace_me'}
 */
final class ReplaceTest extends TestCase
{
    public function testReplace() : void
    {
        $space = $this->client->getSpace('request_replace');

        self::assertSame([[2, 'replace_me']], $space->select(Criteria::key([2])));
        self::assertSame([[2, 'replaced']], $space->replace([2, 'replaced']));
    }
}

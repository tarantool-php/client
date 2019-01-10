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
final class ReplaceTest extends TestCase
{
    public function testReplace() : void
    {
        $space = $this->client->getSpace('space_misc');

        self::assertSame([[2, 'replace_me']], $space->select([2])->getData());

        $response = $space->replace([2, 'replaced']);

        self::assertSame([[2, 'replaced']], $response->getData());
    }
}

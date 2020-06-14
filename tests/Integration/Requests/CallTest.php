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

use Tarantool\Client\Tests\Integration\TestCase;

final class CallTest extends TestCase
{
    public function testCall() : void
    {
        self::assertArrayHasKey('version', $this->client->call('box.info')[0]);
    }

    public function testCallWithArgs() : void
    {
        self::assertSame([1], $this->client->call('math.min', 3, 1, 5));
    }
}

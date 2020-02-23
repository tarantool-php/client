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

use Tarantool\Client\Exception\RequestFailed;

final class BoxErrorTest extends TestCase
{
    public function testExceptionIsThrownOnBoxError() : void
    {
        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage('Foobar');
        $this->expectExceptionCode(42);

        $this->client->evaluate('box.error{code = 42, reason = "Foobar"}');
    }
}

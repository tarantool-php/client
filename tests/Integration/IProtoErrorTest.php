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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Exception\Exception;

final class IProtoErrorTest extends TestCase
{
    public function testExceptionIsThrownOnError() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Foobar.');
        $this->expectExceptionCode(42);

        $this->client->evaluate('box.error{code = 42, reason = "Foobar."}');
    }
}

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

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Response;

final class ResponseTest extends TestCase
{
    public function testHasBodyKeyReturnsTrue() : void
    {
        $response = new Response([], [42 => null]);

        self::assertTrue($response->hasBodyField(42));
    }

    public function testHasBodyKeyReturnsFalse() : void
    {
        $response = new Response([], [24 => null]);

        self::assertFalse($response->hasBodyField(42));
    }
}

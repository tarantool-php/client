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

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Keys;
use Tarantool\Client\Response;

final class ResponseTest extends TestCase
{
    public function testGetSchemaIdReturnsCorrectId() : void
    {
        $schemaId = 42;
        $response = new Response([Keys::SCHEMA_ID => $schemaId], []);

        self::assertSame($schemaId, $response->getSchemaId());
    }

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

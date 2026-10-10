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
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Keys;
use Tarantool\Client\Response;

final class ResponseTest extends TestCase
{
    public function testGetSchemaIdReturnsCorrectId() : void
    {
        $schemaId = 42;
        $response = new Response([Keys::CODE => 0, Keys::SYNC => 1, Keys::SCHEMA_ID => $schemaId], []);

        self::assertSame($schemaId, $response->getSchemaId());
    }

    public function testConstructorRejectsMissingCode() : void
    {
        $this->expectException(UnexpectedResponse::class);
        new Response([Keys::SYNC => 1], []);
    }

    public function testConstructorRejectsInvalidCodeType() : void
    {
        $this->expectException(UnexpectedResponse::class);
        new Response([Keys::CODE => '0', Keys::SYNC => 1], []);
    }

    public function testConstructorRejectsMissingSync() : void
    {
        $this->expectException(UnexpectedResponse::class);
        new Response([Keys::CODE => 0], []);
    }

    public function testConstructorRejectsInvalidSchemaIdType() : void
    {
        $this->expectException(UnexpectedResponse::class);
        new Response([Keys::CODE => 0, Keys::SYNC => 1, Keys::SCHEMA_ID => null], []);
    }

    public function testConstructorRejectsMissingSchemaId() : void
    {
        $this->expectException(UnexpectedResponse::class);
        new Response([Keys::CODE => 0, Keys::SYNC => 1], []);
    }

    public function testGetBodyFieldRejectsMissingFieldAsUnexpectedResponse() : void
    {
        $response = new Response([Keys::CODE => 0, Keys::SYNC => 1, Keys::SCHEMA_ID => 0], []);

        $this->expectException(UnexpectedResponse::class);
        $response->getBodyField(Keys::DATA);
    }

    public function testHasBodyKeyReturnsTrue() : void
    {
        $response = new Response([Keys::CODE => 0, Keys::SYNC => 1, Keys::SCHEMA_ID => 0], [42 => null]);

        self::assertTrue($response->hasBodyField(42));
    }

    public function testHasBodyKeyReturnsFalse() : void
    {
        $response = new Response([Keys::CODE => 0, Keys::SYNC => 1, Keys::SCHEMA_ID => 0], [24 => null]);

        self::assertFalse($response->hasBodyField(42));
    }
}

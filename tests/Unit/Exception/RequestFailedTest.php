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

namespace Tarantool\Client\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Error;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Keys;
use Tarantool\Client\Response;

final class RequestFailedTest extends TestCase
{
    public function testUsesStructuredErrorWhenLegacyMessageIsMissing() : void
    {
        $error = new Error('ClientError', 'file.lua', 1, 'The server error', 1, 42);
        $response = new Response(
            [Keys::CODE => Response::TYPE_ERROR + 42, Keys::SYNC => 1, Keys::SCHEMA_ID => 0],
            [Keys::ERROR => $error->toMap()]
        );

        $requestFailed = RequestFailed::fromErrorResponse($response);
        $actualError = $requestFailed->getError();

        self::assertSame('The server error', $requestFailed->getMessage());
        self::assertNotNull($actualError);
        self::assertSame($error->getMessage(), $actualError->getMessage());
    }

    public function testMissingErrorMessageWithoutStructuredErrorIsUnexpectedResponse() : void
    {
        $response = new Response([Keys::CODE => Response::TYPE_ERROR, Keys::SYNC => 1, Keys::SCHEMA_ID => 0], []);

        $this->expectException(UnexpectedResponse::class);
        RequestFailed::fromErrorResponse($response);
    }

    public function testRejectsNonArrayStructuredError() : void
    {
        $response = new Response(
            [Keys::CODE => Response::TYPE_ERROR, Keys::SYNC => 1, Keys::SCHEMA_ID => 0],
            [Keys::ERROR => 'malformed', Keys::ERROR_24 => 'The server error']
        );

        $this->expectException(UnexpectedResponse::class);
        RequestFailed::fromErrorResponse($response);
    }

    public function testTranslatesMalformedStructuredErrorMap() : void
    {
        $response = new Response(
            [Keys::CODE => Response::TYPE_ERROR, Keys::SYNC => 1, Keys::SCHEMA_ID => 0],
            [Keys::ERROR => [Keys::ERROR_STACK => []], Keys::ERROR_24 => 'The server error']
        );

        $this->expectException(UnexpectedResponse::class);
        RequestFailed::fromErrorResponse($response);
    }
}

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

namespace App;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Keys;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\CallRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class LegacyCallMiddleware implements Middleware
{
    /** @var bool */
    private $useLegacyMarshalling;

    /**
     * @param bool $useLegacyMarshalling
     */
    private function __construct($useLegacyMarshalling)
    {
        $this->useLegacyMarshalling = $useLegacyMarshalling;
    }

    public static function legacyResponseMarshalling() : self
    {
        return new self(true);
    }

    public static function newResponseMarshalling() : self
    {
        return new self(false);
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $response = $request instanceof CallRequest
            ? $handler->handle(LegacyCallRequest::fromCallRequest($request))
            : $handler->handle($request);

        return $this->useLegacyMarshalling ? $response : new Response([
            Keys::CODE => $response->getCode(),
            Keys::SYNC => $response->getSync(),
            Keys::SCHEMA_ID => $response->getSchemaId(),
        ], [
            Keys::DATA => $response->getBodyField(Keys::DATA)[0],
        ]);
    }
}

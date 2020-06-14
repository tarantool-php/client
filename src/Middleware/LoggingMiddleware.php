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

namespace Tarantool\Client\Middleware;

use Psr\Log\LoggerInterface;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\RequestTypes;
use Tarantool\Client\Response;

final class LoggingMiddleware implements Middleware
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $requestName = RequestTypes::getName($request->getType());

        $this->logger->debug("Starting handling request \"$requestName\"", [
            'request' => $request,
        ]);

        $start = \microtime(true);
        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            $this->logger->error("Request \"$requestName\" failed", [
                'request' => $request,
                'exception' => $e,
                'duration_ms' => \round((\microtime(true) - $start) * 1000),
            ]);

            throw $e;
        }

        $this->logger->debug("Finished handling request \"$requestName\"", [
            'request' => $request,
            'response' => $response,
            'duration_ms' => \round((\microtime(true) - $start) * 1000),
        ]);

        return $response;
    }
}

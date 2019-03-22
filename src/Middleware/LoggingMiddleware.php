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

namespace Tarantool\Client\Middleware;

use Psr\Log\LoggerInterface;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
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
        $this->logger->debug('Starting handling request "{class}"', [
            'request' => $request,
            'class' => $requestClass = \get_class($request),
        ]);

        $response = $handler->handle($request);

        $this->logger->debug('Finished handling request "{class}"', [
            'response' => $response,
            'class' => $requestClass,
        ]);

        return $response;
    }
}

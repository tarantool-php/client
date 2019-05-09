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

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class RetryMiddleware implements Middleware
{
    public const DEFAULT_MAX_RETRIES = 3;

    private $getDelay;

    private function __construct(\Closure $getDelay)
    {
        $this->getDelay = $getDelay;
    }

    public static function constant(int $maxRetries = self::DEFAULT_MAX_RETRIES, int $uInterval = 1000) : self
    {
        return new self(static function (int $retries) use ($maxRetries, $uInterval) {
            return $retries > $maxRetries ? null : $uInterval;
        });
    }

    public static function exponential(int $maxRetries = self::DEFAULT_MAX_RETRIES, int $uBase = 1000) : self
    {
        return new self(static function (int $retries) use ($maxRetries, $uBase) {
            return $retries > $maxRetries ? null : $uBase ** $retries;
        });
    }

    public static function linear(int $maxRetries = self::DEFAULT_MAX_RETRIES, int $uStep = 1000) : self
    {
        return new self(static function (int $retries) use ($maxRetries, $uStep) {
            return $retries > $maxRetries ? null : $uStep * $retries;
        });
    }

    public static function custom(\Closure $getDelay) : self
    {
        return new self(static function (int $retries) use ($getDelay) : ?int {
            return $getDelay($retries);
        });
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $retries = 0;

        do {
            try {
                return $handler->handle($request);
            } catch (\Throwable $e) {
                if (null === $delay = ($this->getDelay)(++$retries)) {
                    break;
                }
                \usleep($delay);
            }
        } while (true);

        throw $e;
    }
}

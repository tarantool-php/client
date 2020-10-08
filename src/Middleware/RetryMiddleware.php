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

use Tarantool\Client\Exception\ClientException;
use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;
use Tarantool\Client\Exception\UnexpectedResponse;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class RetryMiddleware implements Middleware
{
    private const DEFAULT_MAX_RETRIES = 2;
    private const MAX_RETRIES_LIMIT = 10;
    private const MAX_DELAY_MS = 60000;

    /** @var \Closure */
    private $getDelayMs;

    /**
     * @param \Closure $getDelayMs
     */
    private function __construct($getDelayMs)
    {
        $this->getDelayMs = $getDelayMs;
    }

    public static function constant(int $maxRetries = self::DEFAULT_MAX_RETRIES, int $intervalMs = 100) : self
    {
        return new self(static function (int $retries) use ($maxRetries, $intervalMs) {
            return $retries > $maxRetries ? null : $intervalMs;
        });
    }

    public static function exponential(int $maxRetries = self::DEFAULT_MAX_RETRIES, int $baseMs = 10) : self
    {
        return new self(static function (int $retries) use ($maxRetries, $baseMs) {
            return $retries > $maxRetries ? null : $baseMs ** $retries;
        });
    }

    public static function linear(int $maxRetries = self::DEFAULT_MAX_RETRIES, int $differenceMs = 100) : self
    {
        return new self(static function (int $retries) use ($maxRetries, $differenceMs) {
            return $retries > $maxRetries ? null : $differenceMs * $retries;
        });
    }

    public static function custom(\Closure $getDelayMs) : self
    {
        return new self(static function (int $retries, \Throwable $e) use ($getDelayMs) : ?int {
            return $getDelayMs($retries, $e);
        });
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $retries = 0;

        do {
            try {
                return $handler->handle($request);
            } catch (UnexpectedResponse $e) {
                $handler->getConnection()->close();
                break;
            } catch (ConnectionFailed | CommunicationFailed $e) {
                $handler->getConnection()->close();
                goto retry;
            } catch (ClientException $e) {
                retry:
                if (self::MAX_RETRIES_LIMIT === $retries) {
                    break;
                }
                if (null === $delayMs = ($this->getDelayMs)(++$retries, $e)) {
                    break;
                }
                $delayMs = \min($delayMs, self::MAX_DELAY_MS) / 2;
                $delayMs += \mt_rand(0, $delayMs);
                \usleep($delayMs * 1000);
            }
        } while (true);

        throw $e;
    }
}

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

namespace Tarantool\Client\Tests;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class SpyMiddleware implements Middleware
{
    private $traceLog;
    private $getTraceValue;

    private function __construct(\Closure $getTraceValue, ?\ArrayObject $traceLog = null)
    {
        $this->getTraceValue = $getTraceValue;
        $this->traceLog = $traceLog ?: new \ArrayObject();
    }

    public static function fromTraceId($traceId, ?\ArrayObject $traceLog = null) : self
    {
        return new self(static function () use ($traceId) {
            return $traceId;
        }, $traceLog);
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $this->traceLog->append(($this->getTraceValue)());

        return $handler->handle($request);
    }

    public function getTraceLog() : \ArrayObject
    {
        return $this->traceLog;
    }

    public function getTraceLogArray() : array
    {
        return $this->traceLog->getArrayCopy();
    }
}

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

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Logger;
use Tarantool\Client\RequestTypes;

final class SlowRequestHandler extends HandlerWrapper
{
    private $thresholdMs;
    private $level;

    public function __construct(HandlerInterface $handler, int $thresholdMs, int $level = Logger::WARNING)
    {
        parent::__construct($handler);

        $this->thresholdMs = $thresholdMs;
        $this->level = $level;
    }

    public function isHandling(array $record) : bool
    {
        // capture all levels
        return true;
    }

    public function handle(array $record) : bool
    {
        if (!isset($record['context']['duration_ms'], $record['context']['request'])) {
            return false;
        }

        if ($record['context']['duration_ms'] <= $this->thresholdMs) {
            return false;
        }

        $request = $record['context']['request'];

        return $this->handler->handle([
            'level' => $this->level,
            'level_name' => Logger::getLevelName($this->level),
            'message' => sprintf('Slow %s request detected (%d ms)', RequestTypes::getName($request->getType()), $record['context']['duration_ms']),
            'context' => ['request_body' => $request->getBody()],
        ] + $record);
    }
}

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

namespace App;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Logger;
use Tarantool\Client\RequestTypes;

final class SlowRequestHandler extends HandlerWrapper
{
    /** @var int */
    private $thresholdMs;

    /** @var int */
    private $level;

    /** @var string */
    private $levelName;

    public function __construct(HandlerInterface $handler, int $thresholdMs, int $level = Logger::WARNING)
    {
        parent::__construct($handler);

        $this->thresholdMs = $thresholdMs;
        $this->level = $level;
        $this->levelName = Logger::getLevelName($this->level);
    }

    public function isHandling(array $record) : bool
    {
        // Handle all levels
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
            'level_name' => $this->levelName,
            'message' => sprintf('Slow %s request detected (%d ms)', RequestTypes::getName($request->getType()), $record['context']['duration_ms']),
            'context' => ['request_body' => $request->getBody()],
        ] + $record);
    }
}

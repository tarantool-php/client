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
use Monolog\Level;
use Monolog\LogRecord;
use Tarantool\Client\RequestTypes;

final class SlowRequestHandler extends HandlerWrapper
{
    private readonly Level $level;

    public function __construct(
        HandlerInterface $handler,
        private readonly int $thresholdMs,
        Level|int $level = Level::Warning,
    ) {
        parent::__construct($handler);

        $this->level = $level instanceof Level ? $level : Level::from($level);
    }

    #[\Override]
    public function isHandling(LogRecord $record) : bool
    {
        // Handle all levels
        return true;
    }

    #[\Override]
    public function handle(LogRecord $record) : bool
    {
        if (!isset($record['context']['duration_ms'], $record['context']['request'])) {
            return false;
        }

        if ($record['context']['duration_ms'] <= $this->thresholdMs) {
            return false;
        }

        $request = $record['context']['request'];

        return $this->handler->handle($record->with(
            level: $this->level,
            message: sprintf('Slow %s request detected (%d ms)', RequestTypes::getName($request->getType()), $record['context']['duration_ms']),
            context: ['request_body' => $request->getBody()] + $record['context']
        ));
    }
}

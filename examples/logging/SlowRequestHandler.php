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
use Tarantool\Client\RequestTypes;

final class SlowRequestHandler extends HandlerWrapper
{
    /** @var int */
    private $thresholdMs;

    /** @var \Monolog\Level */
    private $level;

    public function __construct(HandlerInterface $handler, int $thresholdMs, \Monolog\Level|int $level = \Monolog\Level::Warning)
    {
        parent::__construct($handler);

        $this->thresholdMs = $thresholdMs;
        $this->level = $level instanceof \Monolog\Level ? $level : \Monolog\Level::from($level);
    }

    #[\Override]
    public function isHandling(\Monolog\LogRecord $record) : bool
    {
        // Handle all levels
        return true;
    }

    #[\Override]
    public function handle(\Monolog\LogRecord $record) : bool
    {
        if (!isset($record['context']['duration_ms'], $record['context']['request'])) {
            return false;
        }

        if ($record['context']['duration_ms'] <= $this->thresholdMs) {
            return false;
        }

        $request = $record['context']['request'];

        $newRecord = $record->with(
            level: $this->level,
            message: sprintf('Slow %s request detected (%d ms)', RequestTypes::getName($request->getType()), $record['context']['duration_ms']),
            context: ['request_body' => $request->getBody()] + $record['context']
        );

        return $this->handler->handle($newRecord);
    }
}

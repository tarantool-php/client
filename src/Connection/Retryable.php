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

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;

final class Retryable implements Connection
{
    private const DEFAULT_MAX_RETRIES = 3;

    private $connection;
    private $maxRetries;

    public function __construct(Connection $connection, int $maxRetries = self::DEFAULT_MAX_RETRIES)
    {
        $this->connection = $connection;
        $this->maxRetries = $maxRetries;
    }

    public function open() : string
    {
        $retry = 0;

        do {
            try {
                return $this->connection->open();
            } catch (ConnectionFailed | CommunicationFailed $e) {
            }
            ++$retry;
        } while ($retry <= $this->maxRetries);

        throw $e;
    }

    public function close() : void
    {
        $this->connection->close();
    }

    public function isClosed() : bool
    {
        return $this->connection->isClosed();
    }

    public function send(string $data) : string
    {
        return $this->connection->send($data);
    }
}

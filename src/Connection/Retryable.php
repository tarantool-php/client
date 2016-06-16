<?php

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\ConnectionException;

class Retryable implements Connection
{
    const DEFAULT_MAX_RETRIES = 3;

    private $connection;
    private $maxRetries;

    public function __construct(Connection $connection, $maxRetries = null)
    {
        $this->connection = $connection;
        $this->maxRetries = null === $maxRetries ? self::DEFAULT_MAX_RETRIES : $maxRetries;
    }

    public function open()
    {
        $retry = 0;

        do {
            try {
                return $this->connection->open();
            } catch (ConnectionException $e) {
            }
            ++$retry;
        } while ($retry <= $this->maxRetries);

        throw $e;
    }

    public function close()
    {
        $this->connection->close();
    }

    public function isClosed()
    {
        return $this->connection->isClosed();
    }

    public function send($data)
    {
        return $this->connection->send($data);
    }
}

<?php

namespace Tarantool\Connection;

use Tarantool\Exception\ConnectionException;

class Retryable implements Connection
{
    const DEFAULT_MAX_ATTEMPTS = 3;

    private $connection;
    private $maxAttempts;

    public function __construct(Connection $connection, $maxAttempts = null)
    {
        $this->connection = $connection;
        $this->maxAttempts = $maxAttempts ?: self::DEFAULT_MAX_ATTEMPTS;
    }

    public function open()
    {
        for ($attempt = $this->maxAttempts; $attempt; $attempt--) {
            try {
                return $this->connection->open();
            } catch (ConnectionException $e) {
            }
        }

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

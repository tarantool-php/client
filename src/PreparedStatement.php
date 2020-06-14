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

namespace Tarantool\Client;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\ExecuteRequest;
use Tarantool\Client\Request\PrepareRequest;

final class PreparedStatement
{
    private $handler;
    private $id;
    private $bindCount;
    private $bindMetadata;
    private $metadata;

    public function __construct(Handler $handler, int $id, int $bindCount, array $bindMetadata, array $metadata)
    {
        $this->handler = $handler;
        $this->id = $id;
        $this->bindCount = $bindCount;
        $this->bindMetadata = $bindMetadata;
        $this->metadata = $metadata;
    }

    /**
     * @param mixed ...$params
     */
    public function execute(...$params) : Response
    {
        return $this->handler->handle(
            ExecuteRequest::fromStatementId($this->id, $params)
        );
    }

    /**
     * @param mixed ...$params
     */
    public function executeQuery(...$params) : SqlQueryResult
    {
        $response = $this->handler->handle(
            ExecuteRequest::fromStatementId($this->id, $params)
        );

        return new SqlQueryResult(
            $response->getBodyField(Keys::DATA),
            $response->getBodyField(Keys::METADATA)
        );
    }

    /**
     * @param mixed ...$params
     */
    public function executeUpdate(...$params) : SqlUpdateResult
    {
        $response = $this->handler->handle(
            ExecuteRequest::fromStatementId($this->id, $params)
        );

        return new SqlUpdateResult($response->getBodyField(Keys::SQL_INFO));
    }

    public function close() : void
    {
        $this->handler->handle(PrepareRequest::fromStatementId($this->id));
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getBindCount() : int
    {
        return $this->bindCount;
    }

    public function getBindMetadata() : array
    {
        return $this->bindMetadata;
    }

    public function getMetadata() : array
    {
        return $this->metadata;
    }
}

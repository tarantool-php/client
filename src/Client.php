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

use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\AuthenticationMiddleware;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Request\CallRequest;
use Tarantool\Client\Request\EvaluateRequest;
use Tarantool\Client\Request\ExecuteRequest;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\PrepareRequest;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Space;

final class Client
{
    /** @var Handler */
    private $handler;

    /** @var array<array-key, Space> */
    private $spaces = [];

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public static function fromDefaults() : self
    {
        return new self(new DefaultHandler(
            StreamConnection::createTcp(),
            PurePacker::fromAvailableExtensions()
        ));
    }

    public static function fromOptions(array $options, ?Packer $packer = null) : self
    {
        $connectionOptions = [];
        if (isset($options['connect_timeout'])) {
            $connectionOptions['connect_timeout'] = $options['connect_timeout'];
        }
        if (isset($options['socket_timeout'])) {
            $connectionOptions['socket_timeout'] = $options['socket_timeout'];
        }
        if (isset($options['tcp_nodelay'])) {
            $connectionOptions['tcp_nodelay'] = $options['tcp_nodelay'];
        }
        if (isset($options['persistent'])) {
            $connectionOptions['persistent'] = $options['persistent'];
        }

        $middleware = [];
        if (isset($options['max_retries']) && 0 !== $options['max_retries']) {
            $middleware[] = RetryMiddleware::linear($options['max_retries']);
        }
        if (isset($options['username'])) {
            $middleware[] = new AuthenticationMiddleware($options['username'], $options['password'] ?? '');
        }

        $connection = isset($options['uri'])
            ? StreamConnection::create($options['uri'], $connectionOptions)
            : StreamConnection::createTcp(StreamConnection::DEFAULT_TCP_URI, $connectionOptions);

        $handler = new DefaultHandler($connection, $packer ?? PurePacker::fromAvailableExtensions());

        return $middleware
            ? new self(MiddlewareHandler::append($handler, $middleware))
            : new self($handler);
    }

    public static function fromDsn(string $dsn, ?Packer $packer = null) : self
    {
        $dsn = Dsn::parse($dsn);

        $connectionOptions = [];
        if (null !== $timeout = $dsn->getFloat('connect_timeout')) {
            $connectionOptions['connect_timeout'] = $timeout;
        }
        if (null !== $timeout = $dsn->getFloat('socket_timeout')) {
            $connectionOptions['socket_timeout'] = $timeout;
        }
        if (null !== $tcpNoDelay = $dsn->getBool('tcp_nodelay')) {
            $connectionOptions['tcp_nodelay'] = $tcpNoDelay;
        }
        if (null !== $persistent = $dsn->getBool('persistent')) {
            $connectionOptions['persistent'] = $persistent;
        }

        $middleware = [];
        if ($maxRetries = $dsn->getInt('max_retries')) {
            $middleware[] = RetryMiddleware::linear($maxRetries);
        }
        if ($username = $dsn->getUsername()) {
            $middleware[] = new AuthenticationMiddleware($username, $dsn->getPassword() ?? '');
        }

        $connection = $dsn->isTcp()
            ? StreamConnection::createTcp($dsn->getConnectionUri(), $connectionOptions)
            : StreamConnection::createUds($dsn->getConnectionUri(), $connectionOptions);

        $handler = new DefaultHandler($connection, $packer ?? PurePacker::fromAvailableExtensions());

        return $middleware
            ? new self(MiddlewareHandler::append($handler, $middleware))
            : new self($handler);
    }

    public function withMiddleware(Middleware ...$middleware) : self
    {
        $new = clone $this;
        $new->handler = MiddlewareHandler::append($new->handler, $middleware);

        return $new;
    }

    public function withPrependedMiddleware(Middleware ...$middleware) : self
    {
        $new = clone $this;
        $new->handler = MiddlewareHandler::prepend($new->handler, $middleware);

        return $new;
    }

    public function getHandler() : Handler
    {
        return $this->handler;
    }

    public function getSpace(string $spaceName) : Space
    {
        if (isset($this->spaces[$spaceName])) {
            return $this->spaces[$spaceName];
        }

        $spaceId = $this->getSpaceIdByName($spaceName);

        return $this->spaces[$spaceName] = $this->spaces[$spaceId] = new Space($this->handler, $spaceId);
    }

    public function getSpaceById(int $spaceId) : Space
    {
        if (isset($this->spaces[$spaceId])) {
            return $this->spaces[$spaceId];
        }

        return $this->spaces[$spaceId] = new Space($this->handler, $spaceId);
    }

    public function ping() : void
    {
        $this->handler->handle(new PingRequest());
    }

    /**
     * @param mixed ...$args
     */
    public function call(string $funcName, ...$args) : array
    {
        return $this->handler->handle(new CallRequest($funcName, $args))
            ->getBodyField(Keys::DATA);
    }

    /**
     * @param mixed ...$args
     */
    public function evaluate(string $expr, ...$args) : array
    {
        return $this->handler->handle(new EvaluateRequest($expr, $args))
            ->getBodyField(Keys::DATA);
    }

    /**
     * @param mixed ...$params
     */
    public function execute(string $sql, ...$params) : Response
    {
        return $this->handler->handle(ExecuteRequest::fromSql($sql, $params));
    }

    /**
     * @param mixed ...$params
     */
    public function executeQuery(string $sql, ...$params) : SqlQueryResult
    {
        $response = $this->handler->handle(ExecuteRequest::fromSql($sql, $params));

        return new SqlQueryResult(
            $response->getBodyField(Keys::DATA),
            $response->getBodyField(Keys::METADATA)
        );
    }

    /**
     * @param mixed ...$params
     */
    public function executeUpdate(string $sql, ...$params) : SqlUpdateResult
    {
        $response = $this->handler->handle(ExecuteRequest::fromSql($sql, $params));

        return new SqlUpdateResult($response->getBodyField(Keys::SQL_INFO));
    }

    public function prepare(string $sql) : PreparedStatement
    {
        $response = $this->handler->handle(PrepareRequest::fromSql($sql));

        return new PreparedStatement(
            $this->handler,
            $response->getBodyField(Keys::STMT_ID),
            $response->getBodyField(Keys::BIND_COUNT),
            $response->getBodyField(Keys::BIND_METADATA),
            $response->tryGetBodyField(Keys::METADATA, [])
        );
    }

    public function flushSpaces() : void
    {
        $this->spaces = [];
    }

    public function __clone()
    {
        $this->spaces = [];
    }

    private function getSpaceIdByName(string $spaceName) : int
    {
        $schema = $this->getSpaceById(Space::VSPACE_ID);
        $data = $schema->select(Criteria::key([$spaceName])->andIndex(Space::VSPACE_NAME_INDEX));

        if ($data) {
            return $data[0][0];
        }

        throw RequestFailed::unknownSpace($spaceName);
    }
}

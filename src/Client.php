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

namespace Tarantool\Client;

use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\AuthMiddleware;
use Tarantool\Client\Middleware\Middleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Request\Call;
use Tarantool\Client\Request\Evaluate;
use Tarantool\Client\Request\Execute;
use Tarantool\Client\Request\Ping;
use Tarantool\Client\Schema\Index;
use Tarantool\Client\Schema\Space;

final class Client
{
    private $handler;
    private $spaces = [];

    public function __construct(Handler $handler)
    {
        $this->handler = $handler;
    }

    public static function fromDefaults() : self
    {
        return new self(new DefaultHandler(
            StreamConnection::createTcp(),
            new PurePacker()
        ));
    }

    public static function fromOptions(array $options) : self
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

        $connection = StreamConnection::create($options['uri'] ?? StreamConnection::DEFAULT_URI, $connectionOptions);
        $handler = new DefaultHandler($connection, new PurePacker());

        if (isset($options['username'])) {
            $handler = new MiddlewareHandler(
                new AuthMiddleware($options['username'], $options['password'] ?? ''),
                $handler
            );
        }
        if (isset($options['max_retries'])) {
            $handler = new MiddlewareHandler(
                RetryMiddleware::linear($options['max_retries']),
                $handler
            );
        }

        return new self($handler);
    }

    public static function fromDsn(string $dsn) : self
    {
        $dsn = Dsn::parse($dsn);

        $connectionOptions = [];
        if (null !== $timeout = $dsn->getInt('connect_timeout')) {
            $connectionOptions['connect_timeout'] = $timeout;
        }
        if (null !== $timeout = $dsn->getInt('socket_timeout')) {
            $connectionOptions['socket_timeout'] = $timeout;
        }
        if (null !== $tcpNoDelay = $dsn->getBool('socket_timeout')) {
            $connectionOptions['tcp_nodelay'] = $tcpNoDelay;
        }

        $connection = $dsn->isTcp()
            ? StreamConnection::createTcp($dsn->getConnectionUri(), $connectionOptions)
            : StreamConnection::createUds($dsn->getConnectionUri(), $connectionOptions);

        $handler = new DefaultHandler($connection, new PurePacker());

        if ($username = $dsn->getUsername()) {
            $handler = new MiddlewareHandler(
                new AuthMiddleware($username, $dsn->getPassword() ?? ''),
                $handler
            );
        }
        if ($maxRetries = $dsn->getInt('max_retries')) {
            $handler = new MiddlewareHandler(
                RetryMiddleware::linear($maxRetries),
                $handler
            );
        }

        return new self($handler);
    }

    public function withMiddleware(Middleware $middleware, Middleware ...$middlewares) : self
    {
        $new = clone $this;
        $new->handler = new MiddlewareHandler($middleware, $this->handler);

        if ($middlewares) {
            $new->handler = MiddlewareHandler::create($new->handler, $middlewares);
        }

        return $new;
    }

    public function getHandler() : Handler
    {
        return $this->handler;
    }

    public function ping() : void
    {
        $this->handler->handle(new Ping());
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

    public function call(string $funcName, ...$args) : array
    {
        $request = new Call($funcName, $args);

        return $this->handler->handle($request)->getBodyField(IProto::DATA);
    }

    public function executeQuery(string $sql, ...$params) : SqlQueryResult
    {
        $request = new Execute($sql, $params);
        $response = $this->handler->handle($request);

        return new SqlQueryResult(
            $response->getBodyField(IProto::DATA),
            $response->getBodyField(IProto::METADATA)
        );
    }

    public function executeUpdate(string $sql, ...$params) : int
    {
        $request = new Execute($sql, $params);

        return $this->handler->handle($request)->getBodyField(IProto::SQL_INFO)[0];
    }

    public function evaluate(string $expr, ...$args) : array
    {
        $request = new Evaluate($expr, $args);

        return $this->handler->handle($request)->getBodyField(IProto::DATA);
    }

    public function flushSpaces() : void
    {
        $this->spaces = [];
    }

    private function getSpaceIdByName(string $spaceName) : int
    {
        $schema = $this->getSpaceById(Space::VSPACE_ID);
        $data = $schema->select([$spaceName], Index::SPACE_NAME);

        if (empty($data)) {
            throw RequestFailed::unknownSpace($spaceName);
        }

        return $data[0][0];
    }
}

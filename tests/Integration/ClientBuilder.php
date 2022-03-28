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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\AuthenticationMiddleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;

final class ClientBuilder
{
    private const DEFAULT_TCP_HOST = '127.0.0.1';
    private const DEFAULT_TCP_PORT = 3301;

    private $packerFactory;
    private $uri;
    private $options = [];
    private $connectionOptions = [];

    public function setPackerFactory(\Closure $factory) : self
    {
        $this->packerFactory = $factory;

        return $this;
    }

    public function setOptions(array $options) : self
    {
        $this->options = $options;

        return $this;
    }

    public function setConnectionOptions(array $options) : self
    {
        $this->connectionOptions = $options;

        return $this;
    }

    public function isTcpConnection() : bool
    {
        return 0 === strpos($this->uri, 'tcp:');
    }

    public function setHost(string $host) : self
    {
        $port = parse_url($this->uri, PHP_URL_PORT);
        $this->uri = sprintf('tcp://%s:%d', $host, $port ?: self::DEFAULT_TCP_PORT);

        return $this;
    }

    public function setPort(int $port) : self
    {
        $host = parse_url($this->uri, PHP_URL_HOST);
        $this->uri = sprintf('tcp://%s:%d', $host ?: self::DEFAULT_TCP_HOST, $port);

        return $this;
    }

    public function setUri(string $uri) : self
    {
        if (0 === strpos($uri, '/')) {
            $uri = 'unix://'.$uri;
        } elseif (0 === strpos($uri, 'unix/:')) {
            $uri = 'unix://'.substr($uri, 6);
        } elseif (!preg_match('/[\D]/', $uri)) {
            $uri = 'tcp://127.0.0.1:'.$uri;
        } elseif (0 !== strpos($uri, 'tcp://') && (0 !== strpos($uri, 'unix://'))) {
            $uri = 'tcp://'.$uri;
        }

        $this->uri = $uri;

        return $this;
    }

    public function getUri() : string
    {
        return $this->uri;
    }

    public function build() : Client
    {
        $connection = $this->createConnection();
        $packer = $this->createPacker();
        $handler = new DefaultHandler($connection, $packer);

        $middlewares = [];
        if (isset($this->options['max_retries'])) {
            $middlewares[] = RetryMiddleware::linear($this->options['max_retries']);
        }
        if (isset($this->options['username'])) {
            $middlewares[] = new AuthenticationMiddleware($this->options['username'], $this->options['password'] ?? '');
        }
        if ($middlewares) {
            $handler = MiddlewareHandler::append($handler, $middlewares);
        }

        return new Client($handler);
    }

    public static function createFromEnv() : self
    {
        return (new self())
            ->setUri(getenv('TNT_LISTEN_URI'));
    }

    public static function createForFakeServer() : self
    {
        $builder = self::createFromEnv();

        if ($builder->isTcpConnection()) {
            $builder->setHost('0.0.0.0');
            $builder->setPort(self::findAvailableTcpPort(8000));
        } else {
            $builder->setUri(sprintf('unix://%s/tnt_client_%s.sock', sys_get_temp_dir(), bin2hex(random_bytes(10))));
        }

        return $builder;
    }

    public function createConnection() : Connection
    {
        if (!$this->uri) {
            throw new \LogicException('Connection URI is not set');
        }

        return StreamConnection::create($this->uri, $this->connectionOptions);
    }

    public function createPacker() : Packer
    {
        return $this->packerFactory ? ($this->packerFactory)() : new PurePacker();
    }

    private static function findAvailableTcpPort(int $min) : int
    {
        $maxTries = 10;
        $try = 0;

        while (true) {
            $port = $min + $try * 500 + random_int(1, 500);
            if (!$fp = @stream_socket_client("tcp://127.0.0.1:$port", $errorCode, $errorMessage, 1)) {
                return $port;
            }

            fclose($fp);

            if (++$try === $maxTries) {
                throw new \RuntimeException('Failed to find open tcp port');
            }
        }
    }
}

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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client;
use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\AuthMiddleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;

final class ClientBuilder
{
    private const PACKER_PURE = 'pure';
    private const PACKER_PECL = 'pecl';

    private const DEFAULT_TCP_HOST = '127.0.0.1';
    private const DEFAULT_TCP_PORT = 3301;

    private $packer;
    private $packerPureFactory;
    private $packerPeclFactory;
    private $uri;
    private $options = [];
    private $connectionOptions = [];

    public function setPacker(string $packer) : self
    {
        $this->packer = $packer;

        return $this;
    }

    public function setPackerPureFactory(\Closure $factory) : self
    {
        $this->packerPureFactory = $factory;

        return $this;
    }

    public function setPackerPeclFactory(\Closure $factory) : self
    {
        $this->packerPeclFactory = $factory;

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
        if (isset($this->options['username'])) {
            $middlewares[] = new AuthMiddleware($this->options['username'], $this->options['password'] ?? '');
        }

        if (isset($this->options['max_retries'])) {
            $middlewares[] = RetryMiddleware::linear($this->options['max_retries']);
        }

        return new Client(MiddlewareHandler::create($handler, $middlewares));
    }

    public static function createFromEnv() : self
    {
        return (new self())
            ->setPacker(getenv('TNT_PACKER'))
            ->setUri(getenv('TNT_CONN_URI'));
    }

    public static function createFromEnvForTheFakeServer() : self
    {
        $builder = self::createFromEnv();

        if ($builder->isTcpConnection()) {
            $builder->setHost('0.0.0.0');
            $builder->setPort(self::findOpenTcpPort(8000));
        } else {
            $builder->setUri(sprintf('unix://%s/tnt_client_%s.sock', sys_get_temp_dir(), bin2hex(random_bytes(10))));
        }

        return $builder;
    }

    private function createConnection() : Connection
    {
        if (!$this->uri) {
            throw new \LogicException('Connection URI is not set.');
        }

        return StreamConnection::create($this->uri, $this->connectionOptions);
    }

    private function createPacker() : Packer
    {
        if (self::PACKER_PURE === $this->packer) {
            return $this->packerPureFactory ? ($this->packerPureFactory)() : new PurePacker();
        }

        if (self::PACKER_PECL === $this->packer) {
            return $this->packerPeclFactory ? ($this->packerPeclFactory)() : new PeclPacker();
        }

        throw new \UnexpectedValueException(sprintf('"%s" packer is not supported.', $this->packer));
    }

    private static function findOpenTcpPort(int $min) : int
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
                throw new \RuntimeException('Failed to find open tcp port.');
            }
        }
    }
}

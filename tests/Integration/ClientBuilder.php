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
use Tarantool\Client\Connection\Retryable;
use Tarantool\Client\Connection\StreamConnection;
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
    private $connectionOptions = [];

    public function setPacker($packer) : self
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

    public function setPort($port) : self
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

        return new Client($connection, $packer);
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
            $builder->setPort(random_int(1024, 65535));
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

        $options = $this->connectionOptions;

        if (isset($options['retries'])) {
            $retries = $options['retries'];
            unset($options['retries']);

            $conn = new StreamConnection($this->uri, $options);

            return new Retryable($conn, $retries);
        }

        return new StreamConnection($this->uri, $options);
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
}

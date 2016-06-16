<?php

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client as TarantoolClient;
use Tarantool\Client\Connection\Retryable;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PeclLitePacker;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Tests\Adapter\Tarantool;

class ClientBuilder
{
    const CLIENT_PURE = 'pure';
    const CLIENT_PECL = 'pecl';

    const PACKER_PURE = 'pure';
    const PACKER_PECL = 'pecl';
    const PACKER_PECL_LITE = 'pecl_lite';

    const DEFAULT_TCP_HOST = '127.0.0.1';
    const DEFAULT_TCP_PORT = 3301;

    private $client;
    private $packer;
    private $uri;
    private $connectionOptions;

    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    public function setPacker($packer)
    {
        $this->packer = $packer;

        return $this;
    }

    public function setConnectionOptions(array $options)
    {
        $this->connectionOptions = $options;

        return $this;
    }

    public function isTcpConnection()
    {
        return 'tcp:' === substr($this->uri, 0, 4);
    }

    public function setHost($host)
    {
        $port = parse_url($this->uri, PHP_URL_PORT);
        $this->uri = sprintf('tcp://%s:%d', $host, $port ?: self::DEFAULT_TCP_PORT);

        return $this;
    }

    public function setPort($port)
    {
        $host = parse_url($this->uri, PHP_URL_HOST);
        $this->uri = sprintf('tcp://%s:%d', $host ?: self::DEFAULT_TCP_HOST, $port);

        return $this;
    }

    public function setUri($uri)
    {
        if ('/' === $uri[0]) {
            $uri = 'unix://'.$uri;
        }

        $this->uri = $uri;

        return $this;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function build()
    {
        if (self::CLIENT_PECL === $this->client) {
            return $this->createPeclClient();
        }

        if (self::CLIENT_PURE === $this->client) {
            $connection = $this->createConnection();
            $packer = $this->createPacker();

            return new TarantoolClient($connection, $packer);
        }

        throw new \UnexpectedValueException(sprintf('"%s" client is not supported.', $this->client));
    }

    /**
     * @return self
     */
    public static function createFromEnv()
    {
        return (new self())
            ->setClient(getenv('TNT_CLIENT'))
            ->setPacker(getenv('TNT_PACKER'))
            ->setUri(getenv('TNT_CONN_URI'));
    }

    private function createConnection()
    {
        $options = $this->connectionOptions;

        if (isset($options['retries'])) {
            $retries = $options['retries'];
            unset($options['retries']);

            $conn = new StreamConnection($this->uri, $options);

            return new Retryable($conn, $retries);
        }

        return new StreamConnection($this->uri, $options);
    }

    private function createPeclClient()
    {
        ini_set('tarantool.timeout', isset($this->connectionOptions['connect_timeout'])
            ? $this->connectionOptions['connect_timeout'] : 10
        );

        ini_set('tarantool.request_timeout', isset($this->connectionOptions['socket_timeout'])
            ? $this->connectionOptions['socket_timeout'] : 10
        );

        ini_set('tarantool.retry_count', isset($this->connectionOptions['retries'])
            ? $this->connectionOptions['retries'] : 0
        );

        $host = parse_url($this->uri, PHP_URL_HOST);
        $port = parse_url($this->uri, PHP_URL_PORT);

        return new Tarantool($host ?: self::DEFAULT_TCP_HOST, $port ?: self::DEFAULT_TCP_PORT);
    }

    private function createPacker()
    {
        if (self::PACKER_PURE === $this->packer) {
            return new PurePacker();
        }

        if (self::PACKER_PECL === $this->packer) {
            return new PeclPacker();
        }

        if (self::PACKER_PECL_LITE === $this->packer) {
            return new PeclLitePacker();
        }

        throw new \UnexpectedValueException(sprintf('"%s" packer is not supported.', $this->packer));
    }
}

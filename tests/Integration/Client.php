<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Client as TarantoolClient;
use Tarantool\Connection\Connection;
use Tarantool\Connection\SocketConnection;
use Tarantool\Tests\Adapter\Tarantool;

trait Client
{
    /**
     * @var TarantoolClient
     */
    private static $client;

    /**
     * @beforeClass
     */
    public static function setUpClient()
    {
        self::$client = self::createClient();
    }

    /**
     * @afterClass
     */
    public static function tearDownClient()
    {
        self::$client = null;
    }

    protected static function createClient($host = null, $port = null)
    {
        $isPecl = 'pecl' === getenv('TARANTOOL_CLIENT');

        if ($host instanceof Connection) {
            if ($isPecl) {
                throw new \LogicException('Creating the pecl client from the Connection instance is not supported.');
            }

            return new TarantoolClient($host);
        }

        $host = null === $host ? getenv('TARANTOOL_HOST') : $host;
        $port = null === $port ? getenv('TARANTOOL_PORT') : $port;

        if ($isPecl) {
            return new Tarantool($host, $port);
        }

        return new TarantoolClient(new SocketConnection($host, $port));
    }

    protected static function getTotalSelectCalls()
    {
        $response = self::$client->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }
}

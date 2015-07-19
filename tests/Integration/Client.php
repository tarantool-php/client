<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Client as TarantoolClient;
use Tarantool\Connection\Connection;

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
        $builder = new ClientBuilder();

        $builder->setClient(getenv('TARANTOOL_CLIENT'));
        $builder->setPacker(getenv('TARANTOOL_PACKER'));

        if ($host instanceof Connection) {
            $builder->setConnection($host);
        } else {
            $builder->setConnection(getenv('TARANTOOL_CONN'));
            $builder->setHost(null === $host ? getenv('TARANTOOL_CONN_HOST') : $host);
            $builder->setPort(null === $port ? getenv('TARANTOOL_CONN_PORT') : $port);
        }

        return $builder->build();
    }

    protected static function getTotalSelectCalls()
    {
        $response = self::$client->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }
}

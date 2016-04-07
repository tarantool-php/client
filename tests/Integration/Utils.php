<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Connection\Connection;

abstract class Utils
{
    public static function createClient($host = null, $port = null, array $connectionOptions = null)
    {
        $builder = new ClientBuilder();

        $builder->setClient(getenv('TNT_CLIENT'));
        $builder->setPacker(getenv('TNT_PACKER'));

        if ($host instanceof Connection) {
            $builder->setConnection($host);

            return $builder->build();
        }

        $builder->setConnection(getenv('TNT_CONN'));

        $builder->setHost(null === $host ? getenv('TNT_CONN_HOST') : $host);
        $builder->setPort(null === $port ? getenv('TNT_CONN_PORT') : $port);
        $builder->setUnixSocket(getenv('TNT_CONN_UNIX'));

        if ($connectionOptions) {
            $builder->setConnectionOptions($connectionOptions);
        }

        return $builder->build();
    }

    public static function getTotalSelectCalls()
    {
        $response = self::createClient()->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }

    public static function getTarantoolVersion()
    {
        $response = self::createClient()->evaluate('return box.info().version');

        return $response->getData()[0];
    }
}

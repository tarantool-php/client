<?php

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Client as PureClient;

trait Client
{
    /**
     * @var PureClient
     */
    private static $client;

    /**
     * @beforeClass
     */
    public static function setUpClient()
    {
        self::$client = ClientBuilder::createFromEnv()->build();
    }

    /**
     * @afterClass
     */
    public static function tearDownClient()
    {
        self::$client = null;
    }
}

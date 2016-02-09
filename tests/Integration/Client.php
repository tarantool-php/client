<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Client as TarantoolClient;

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
        self::$client = Utils::createClient();
    }

    /**
     * @afterClass
     */
    public static function tearDownClient()
    {
        self::$client = null;
    }
}

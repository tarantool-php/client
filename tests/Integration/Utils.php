<?php

namespace Tarantool\Client\Tests\Integration;

abstract class Utils
{
    public static function getTotalSelectCalls()
    {
        $client = ClientBuilder::createFromEnv()->build();
        $response = $client->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }

    public static function getTarantoolVersion()
    {
        $client = ClientBuilder::createFromEnv()->build();
        $response = $client->evaluate('return box.info().version');

        return $response->getData()[0];
    }
}

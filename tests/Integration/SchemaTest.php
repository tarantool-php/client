<?php

namespace Tarantool\Tests\Integration;

use Tarantool\Schema\Space;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
    use Client;

    public function testCacheSpace()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSchema();
        self::$client->select('space_conn');
        self::$client->select('space_conn');

        $this->assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testCacheIndex()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSchema();
        self::$client->select(Space::INDEX, [], 'name');
        self::$client->select(Space::INDEX, [], 'name');

        $this->assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testFlushSchema()
    {
        $total = self::getTotalSelectCalls();

        self::$client->flushSchema();
        self::$client->select('space_conn');
        self::$client->flushSchema();
        self::$client->select('space_conn');

        $this->assertSame(4, self::getTotalSelectCalls() - $total);
    }

    private static function getTotalSelectCalls()
    {
        $response = self::$client->evaluate('return box.stat().SELECT.total');

        return $response->getData()[0];
    }
}

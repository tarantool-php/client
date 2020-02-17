<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Connection\Greeting;
use Tarantool\Client\Tests\GreetingDataProvider;

final class GreetingTest extends TestCase
{
    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideValidGreetings
     */
    public function testGetSalt(string $greeting, string $salt) : void
    {
        self::assertSame($salt, Greeting::parse($greeting)->getSalt());
    }

    /**
     * @dataProvider provideServerVersionData
     */
    public function testGetServerVersion(string $greeting, string $expectedVersion) : void
    {
        $greeting = Greeting::parse($greeting);

        self::assertSame($expectedVersion, $greeting->getServerVersion());
    }

    public function provideServerVersionData() : iterable
    {
        return [
            [GreetingDataProvider::generateGreeting('foobar'), ''],
            [GreetingDataProvider::generateGreeting('0.0.2'), '0.0.2'],
            [GreetingDataProvider::generateGreeting('0.2.0'), '0.2.0'],
            [GreetingDataProvider::generateGreeting('0.2.2'), '0.2.2'],
            [GreetingDataProvider::generateGreeting('2.0.0'), '2.0.0'],
            [GreetingDataProvider::generateGreeting('2.0.2'), '2.0.2'],
            [GreetingDataProvider::generateGreeting('2.2.0'), '2.2.0'],
            [GreetingDataProvider::generateGreeting('2.2.2'), '2.2.2'],
            [GreetingDataProvider::generateGreeting('2.3.4-123.45'), '2.3.4'],
            [GreetingDataProvider::generateGreeting('10.200.3000'), '10.200.3000'],
        ];
    }
}

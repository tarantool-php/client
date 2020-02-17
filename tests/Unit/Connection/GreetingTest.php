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
    public function testGetServerVersion(string $greeting, string $expectedVersion, int $expectedVersionId) : void
    {
        $greeting = Greeting::parse($greeting);

        self::assertSame($expectedVersion, $greeting->getServerVersion());
        self::assertSame($expectedVersionId, $greeting->getServerVersionId());
    }

    public function provideServerVersionData() : iterable
    {
        return [
            [GreetingDataProvider::generateGreeting('foobar'), '', 0],
            [GreetingDataProvider::generateGreeting('0.0.2'), '0.0.2', 2],
            [GreetingDataProvider::generateGreeting('0.2.0'), '0.2.0', 200],
            [GreetingDataProvider::generateGreeting('0.2.2'), '0.2.2', 202],
            [GreetingDataProvider::generateGreeting('2.0.0'), '2.0.0', 20000],
            [GreetingDataProvider::generateGreeting('2.0.2'), '2.0.2', 20002],
            [GreetingDataProvider::generateGreeting('2.2.0'), '2.2.0', 20200],
            [GreetingDataProvider::generateGreeting('2.2.2'), '2.2.2', 20202],
            [GreetingDataProvider::generateGreeting('10.20.30'), '10.20.30', 102030],
        ];
    }
}

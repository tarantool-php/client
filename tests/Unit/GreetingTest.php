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

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Exception\InvalidGreeting;
use Tarantool\Client\Greeting;

final class GreetingTest extends TestCase
{
    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     */
    public function testParseThrowsExceptionOnInvalidServer(string $greeting) : void
    {
        $this->expectException(InvalidGreeting::class);
        $this->expectExceptionMessage('Unable to recognize Tarantool server.');

        Greeting::parse($greeting);
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideValidGreetings
     */
    public function testGetSalt(string $greeting, string $salt) : void
    {
        self::assertSame($salt, Greeting::parse($greeting)->getSalt());
    }

    /**
     * @testWith
     * ["Tarantool foobar", 0]
     * ["Tarantool 0.0.2", 2]
     * ["Tarantool 0.2.0", 200]
     * ["Tarantool 0.2.2", 202]
     * ["Tarantool 2.0.0", 20000]
     * ["Tarantool 2.0.2", 20002]
     * ["Tarantool 2.2.0", 20200]
     * ["Tarantool 2.2.2", 20202]
     * ["Tarantool 10.20.30", 102030]
     */
    public function testGetServerVersion(string $greeting, int $expectedVersion) : void
    {
        self::assertSame($expectedVersion, Greeting::parse($greeting)->getServerVersion());
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     */
    public function testGetInvalidSalt(string $greeting) : void
    {
        $this->expectException(InvalidGreeting::class);
        $this->expectExceptionMessage('Unable to parse salt.');

        Greeting::parse($greeting)->getSalt();
    }
}

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
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideValidGreetings
     */
    public function testParse(string $greeting, string $salt) : void
    {
        self::assertSame($salt, Greeting::parse($greeting));
    }

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
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     */
    public function testParseThrowsExceptionOnInvalidSalt(string $greeting) : void
    {
        $this->expectException(InvalidGreeting::class);
        $this->expectExceptionMessage('Unable to parse salt.');

        Greeting::parse($greeting);
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Exception\InvalidGreeting;
use Tarantool\Client\IProto;

final class IProtoTest extends TestCase
{
    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideValidGreetings
     */
    public function testParseGreeting(string $greeting, string $salt) : void
    {
        self::assertSame($salt, IProto::parseGreeting($greeting));
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidServerName
     */
    public function testParseGreetingThrowsExceptionOnInvalidServer(string $greeting) : void
    {
        $this->expectException(InvalidGreeting::class);
        $this->expectExceptionMessage('Unable to recognize Tarantool server.');

        IProto::parseGreeting($greeting);
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\GreetingDataProvider::provideGreetingsWithInvalidSalt
     */
    public function testParseGreetingThrowsExceptionOnInvalidSalt(string $greeting) : void
    {
        $this->expectException(InvalidGreeting::class);
        $this->expectExceptionMessage('Unable to parse salt.');

        IProto::parseGreeting($greeting);
    }
}

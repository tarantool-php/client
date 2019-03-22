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
use Tarantool\Client\Dsn;

final class DsnTest extends TestCase
{
    /**
     * @dataProvider provideParseValidDsnData
     */
    public function testParseValidDsn(string $dsn, string $uri, array $authority = [], array $options = []) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($uri, $dsn->getConnectionUri());

        if ($authority) {
            self::assertSame($authority[0], $dsn->getUsername());
            self::assertSame($authority[1], $dsn->getPassword());
        }

        foreach ($options as $name => $value) {
            if (is_int($value)) {
                self::assertSame($value, $dsn->getInt($name));
            } elseif (is_bool($value)) {
                self::assertSame($value, $dsn->getBool($name));
            } else {
                self::assertSame($value, $dsn->getString($name));
            }
        }
    }

    public function provideParseValidDsnData() : iterable
    {
        return [
            [
                'tcp://127.0.0.1',
                'tcp://127.0.0.1:3301',
            ], [
                'tcp://localhost',
                'tcp://localhost:3301',
            ],
            [
                'tcp://[fe80::1]',
                'tcp://[fe80::1]:3301',
            ],
            [
                'tcp://[fe80::1]:1234',
                'tcp://[fe80::1]:1234',
            ], [
                'tcp://[de:ad:be:ef::ca:fe]:1234',
                'tcp://[de:ad:be:ef::ca:fe]:1234',
            ], [
                'tcp://foo:bar@[de:ad:be:ef::ca:fe]:1234',
                'tcp://[de:ad:be:ef::ca:fe]:1234',
                ['foo', 'bar'],
            ], [
                'tcp://hostname:1234',
                'tcp://hostname:1234',
            ], [
                'tcp://foo:bar@hostname:1234/?option=42',
                'tcp://hostname:1234',
                ['foo', 'bar'],
                ['option' => 42],
            ], [
                'tcp://hostname:1234/?option=42',
                'tcp://hostname:1234',
                [],
                ['option' => 42],
            ], [
                'tcp://foo.bar.baz.com',
                'tcp://foo.bar.baz.com:3301',
            ], [
                'unix:///path/to/socket.sock',
                'unix:///path/to/socket.sock',
            ], [
                'unix://foo@/path/to/socket.sock',
                'unix:///path/to/socket.sock',
                ['foo', ''],
            ], [
                'unix://foo:bar@/path/to/socket.sock',
                'unix:///path/to/socket.sock',
                ['foo', 'bar'],
            ], [
                'unix://foo:bar@/path/to/socket.sock?opt1=42&opt2=z&opt3=false',
                'unix:///path/to/socket.sock',
                ['foo', 'bar'],
                ['opt1' => 42, 'opt2' => 'z', 'opt3' => false],
            ], [
                'unix:///path/to/socket.sock?opt1=42&opt2=z&opt3=false',
                'unix:///path/to/socket.sock',
                [],
                ['opt1' => 42, 'opt2' => 'z', 'opt3' => false],
            ],

            // https://docs.mongodb.com/manual/reference/connection-string/
            // https://jira.mongodb.org/browse/SERVER-27997
        ];
    }

    /**
     * @dataProvider provideParseInvalidDsnData
     */
    public function testParseInvalidDsn(string $nonDsn) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        Dsn::parse($nonDsn);
    }

    public function provideParseInvalidDsnData() : iterable
    {
        return [
            [''],
            ['foobar'],
            ['tcp:/'],
            ['tcp://'],
            ['unix:/'],
            ['unix://'],
            ['unix:///'],
        ];
    }
}

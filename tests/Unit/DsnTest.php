<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Dsn;

final class DsnTest extends TestCase
{
    /**
     * @dataProvider provideValidTcpDsns
     */
    public function testParseValidTcpDsn(string $dsn, array $expectedResult) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedResult['uri'], $dsn->getConnectionUri());
        self::assertSame($expectedResult['host'], $dsn->getHost());
        self::assertSame($expectedResult['port'], $dsn->getPort());
        self::assertNull($dsn->getPath());
        self::assertTrue($dsn->isTcp());

        if (isset($expectedResult['username'])) {
            self::assertSame($expectedResult['username'], $dsn->getUsername());
            self::assertSame($expectedResult['password'] ?? '', $dsn->getPassword());
        }
    }

    public function provideValidTcpDsns() : iterable
    {
        return [
            ['tcp://127.0.0.1', ['uri' => 'tcp://127.0.0.1:3301', 'host' => '127.0.0.1', 'port' => 3301]],
            ['tcp://localhost', ['uri' => 'tcp://localhost:3301', 'host' => 'localhost', 'port' => 3301]],
            ['tcp://f%40%40:b%40r@localhost', ['uri' => 'tcp://localhost:3301', 'host' => 'localhost', 'port' => 3301, 'username' => 'f@@', 'password' => 'b@r']],
            ['tcp://foo.bar:1234', ['uri' => 'tcp://foo.bar:1234', 'host' => 'foo.bar', 'port' => 1234]],
            ['tcp://foo.bar:1234/?opt=42', ['uri' => 'tcp://foo.bar:1234', 'host' => 'foo.bar', 'port' => 1234]],
            ['tcp://foo.bar.baz.com', ['uri' => 'tcp://foo.bar.baz.com:3301', 'host' => 'foo.bar.baz.com', 'port' => 3301]],
            ['tcp://foo:bar@baz.com:1234/?opt=42', ['uri' => 'tcp://baz.com:1234', 'host' => 'baz.com', 'port' => 1234, 'username' => 'foo', 'password' => 'bar']],
            ['tcp://[fe80::1]', ['uri' => 'tcp://[fe80::1]:3301', 'host' => '[fe80::1]', 'port' => 3301]],
            ['tcp://[fe80::1]:1234', ['uri' => 'tcp://[fe80::1]:1234', 'host' => '[fe80::1]', 'port' => 1234]],
            ['tcp://[de:ad:be:ef::ca:fe]:1234', ['uri' => 'tcp://[de:ad:be:ef::ca:fe]:1234', 'host' => '[de:ad:be:ef::ca:fe]', 'port' => 1234]],
            ['tcp://foo@[de:ad:be:ef::ca:fe]:1234', ['uri' => 'tcp://[de:ad:be:ef::ca:fe]:1234', 'host' => '[de:ad:be:ef::ca:fe]', 'port' => 1234, 'username' => 'foo', 'password' => '']],
        ];
    }

    /**
     * @dataProvider provideValidUdsDsns
     */
    public function testParseValidUdsDsn(string $dsn, array $expected) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expected['uri'], $dsn->getConnectionUri());
        self::assertNull($dsn->getHost());
        self::assertNull($dsn->getPort());
        self::assertSame($expected['path'], $dsn->getPath());
        self::assertFalse($dsn->isTcp());

        if (isset($expected['username'])) {
            self::assertSame($expected['username'], $dsn->getUsername());
            self::assertSame($expected['password'] ?? '', $dsn->getPassword());
        }
    }

    public function provideValidUdsDsns() : iterable
    {
        return [
            ['unix:///path/to/socket.sock', ['uri' => 'unix:///path/to/socket.sock', 'path' => '/path/to/socket.sock']],
            ['unix:///path/to/socket.sock?opt=42', ['uri' => 'unix:///path/to/socket.sock', 'path' => '/path/to/socket.sock']],
            ['unix://foo@/path/to/socket.sock', ['uri' => 'unix:///path/to/socket.sock', 'path' => '/path/to/socket.sock', 'username' => 'foo', 'password' => '']],
            ['unix://foo:bar@/path/to/socket.sock', ['uri' => 'unix:///path/to/socket.sock', 'path' => '/path/to/socket.sock', 'username' => 'foo', 'password' => 'bar']],
            ['unix://foo:bar@/path/to/socket.sock?opt=42', ['uri' => 'unix:///path/to/socket.sock', 'path' => '/path/to/socket.sock', 'username' => 'foo', 'password' => 'bar']],
            ['unix://f%40%40:b%40r@%2fsocket.sock', ['uri' => 'unix://%2fsocket.sock', 'path' => '/socket.sock', 'username' => 'f@@', 'password' => 'b@r']],
        ];
    }

    /**
     * @dataProvider provideInvalidDsns
     */
    public function testParseInvalidDsn(string $nonDsn) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unable to parse DSN "%s"', $nonDsn));
        Dsn::parse($nonDsn);
    }

    public function provideInvalidDsns() : iterable
    {
        return [
            [''],
            ['foobar'],
            ['tcp:'],
            ['tcp:host'],
            ['tcp:/'],
            ['tcp:/host'],
            ['tcp://'],
            ['tcp://host/path'],
            ['tcp:////'],
            ['unix:'],
            ['unix:/'],
            ['unix:/path'],
            ['unix://'],
            ['unix://foo.bar:3030/path'],
            ['unix:////'],
            ['http://host'],
            ['file:///path'],
        ];
    }

    /**
     * @dataProvider provideStringOptions
     */
    public function testGetString(string $dsn, string $option, ?string $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getString($option));
    }

    public function provideStringOptions() : iterable
    {
        return [
            ['tcp://host/?foo=bar', 'foo', 'bar'],
            ['tcp://host/?foo=b%40r', 'foo', 'b@r'],
            ['tcp://host/?foo=', 'foo', ''],
            ['tcp://host/?foo=42', 'foo', '42'],
            ['tcp://host/?foo=%25', 'foo', '%'],
            ['tcp://host/?foo=%2525', 'foo', '%25'],
            ['tcp://host', 'foo', null],
            ['unix:///socket.sock/?foo=bar', 'foo', 'bar'],
            ['unix:///socket.sock/?foo=b%40r', 'foo', 'b@r'],
            ['unix:///socket.sock/?foo=', 'foo', ''],
            ['unix:///socket.sock/?foo=42', 'foo', '42'],
            ['unix:///socket.sock/?foo=%25', 'foo', '%'],
            ['unix:///socket.sock/?foo=%2525', 'foo', '%25'],
            ['unix:///socket.sock', 'foo', null],
        ];
    }

    public function testGetStringDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=bar');
        self::assertSame('qux', $dsn->getString('baz', 'qux'));
    }

    /**
     * @dataProvider provideIntOptions
     */
    public function testGetInt(string $dsn, string $option, $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getInt($option));
    }

    public function provideIntOptions() : iterable
    {
        return [
            ['tcp://host/?foo=42', 'foo', 42],
            ['tcp://host/?foo=0', 'foo', 0],
            ['tcp://host', 'foo', null],
            ['unix:///socket.sock/?foo=42', 'foo', 42],
            ['unix:///socket.sock/?foo=0', 'foo', 0],
            ['unix:///socket.sock', 'foo', null],
        ];
    }

    public function testGetIntDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=2');
        self::assertSame(42, $dsn->getInt('baz', 42));
    }

    /**
     * @dataProvider provideNonIntOptions
     */
    public function testGetNonInt(string $dsn, string $option) : void
    {
        $dsn = Dsn::parse($dsn);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('DSN option "foo" must be of the type int');
        $dsn->getInt($option);
    }

    public function provideNonIntOptions() : iterable
    {
        return [
            ['tcp://host/?foo=bar', 'foo'],
            ['tcp://host/?foo=4.2', 'foo'],
            ['tcp://host/?foo=true', 'foo'],
            ['tcp://host/?foo=', 'foo'],
            ['unix:///socket.sock/?foo=bar', 'foo'],
            ['unix:///socket.sock/?foo=4.2', 'foo'],
            ['unix:///socket.sock/?foo=true', 'foo'],
            ['unix:///socket.sock/?foo=', 'foo'],
        ];
    }

    /**
     * @dataProvider provideFloatOptions
     */
    public function testGetFloat(string $dsn, string $option, $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getFloat($option));
    }

    public function provideFloatOptions() : iterable
    {
        return [
            ['tcp://host/?foo=4.2', 'foo', 4.2],
            ['tcp://host/?foo=0', 'foo', 0.0],
            ['tcp://host', 'foo', null],
            ['unix:///socket.sock/?foo=4.2', 'foo', 4.2],
            ['unix:///socket.sock/?foo=0', 'foo', 0.0],
            ['unix:///socket.sock', 'foo', null],
        ];
    }

    public function testGetFloatDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=2.2');
        self::assertSame(4.2, $dsn->getFloat('baz', 4.2));
    }

    /**
     * @dataProvider provideNonFloatOptions
     */
    public function testGetNonFloat(string $dsn, string $option) : void
    {
        $dsn = Dsn::parse($dsn);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('DSN option "foo" must be of the type float');
        $dsn->getFloat($option);
    }

    public function provideNonFloatOptions() : iterable
    {
        return [
            ['tcp://host/?foo=bar', 'foo'],
            ['tcp://host/?foo=true', 'foo'],
            ['tcp://host/?foo=', 'foo'],
            ['unix:///socket.sock/?foo=bar', 'foo'],
            ['unix:///socket.sock/?foo=true', 'foo'],
            ['unix:///socket.sock/?foo=', 'foo'],
        ];
    }

    /**
     * @dataProvider provideBoolOptions
     */
    public function testGetBool(string $dsn, string $option, ?bool $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getBool($option));
    }

    public function provideBoolOptions() : iterable
    {
        return [
            ['tcp://host/?foo=true', 'foo', true],
            ['tcp://host/?foo=false', 'foo', false],
            ['tcp://host/?foo=on', 'foo', true],
            ['tcp://host/?foo=off', 'foo', false],
            ['tcp://host/?foo=1', 'foo', true],
            ['tcp://host/?foo=0', 'foo', false],
            ['tcp://host/?foo=', 'foo', false],
            ['tcp://host', 'foo', null],
            ['unix:///socket.sock/?foo=true', 'foo', true],
            ['unix:///socket.sock/?foo=false', 'foo', false],
            ['unix:///socket.sock/?foo=on', 'foo', true],
            ['unix:///socket.sock/?foo=off', 'foo', false],
            ['unix:///socket.sock/?foo=1', 'foo', true],
            ['unix:///socket.sock/?foo=0', 'foo', false],
            ['unix:///socket.sock/?foo=', 'foo', false],
            ['unix:///socket.sock', 'foo', null],
        ];
    }

    public function testGetBoolDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=0');
        self::assertTrue($dsn->getBool('baz', true));
    }

    /**
     * @dataProvider provideNonBoolOptions
     */
    public function testGetNonBool(string $dsn, string $option) : void
    {
        $dsn = Dsn::parse($dsn);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('DSN option "foo" must be of the type bool');
        $dsn->getBool($option);
    }

    public function provideNonBoolOptions() : iterable
    {
        return [
            ['tcp://host/?foo=bar', 'foo'],
            ['tcp://host/?foo=42', 'foo'],
            ['unix:///socket.sock/?foo=bar', 'foo'],
            ['unix:///socket.sock/?foo=42', 'foo'],
        ];
    }
}

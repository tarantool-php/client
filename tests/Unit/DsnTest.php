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
     * @testWith
     * ["tcp://127.0.0.1", {"uri": "tcp://127.0.0.1:3301", "host": "127.0.0.1", "port": 3301}]
     * ["tcp://localhost", {"uri": "tcp://localhost:3301", "host": "localhost", "port": 3301}]
     * ["tcp://f%40%40:b%40r@localhost", {"uri": "tcp://localhost:3301", "host": "localhost", "port": 3301, "username": "f@@", "password": "b@r"}]
     * ["tcp://foo.bar:1234", {"uri": "tcp://foo.bar:1234", "host": "foo.bar", "port": 1234}]
     * ["tcp://foo.bar:1234/?opt=42", {"uri": "tcp://foo.bar:1234", "host": "foo.bar", "port": 1234}]
     * ["tcp://foo.bar.baz.com", {"uri": "tcp://foo.bar.baz.com:3301", "host": "foo.bar.baz.com", "port": 3301}]
     * ["tcp://foo:bar@baz.com:1234/?opt=42", {"uri": "tcp://baz.com:1234", "host": "baz.com", "port": 1234, "username": "foo", "password": "bar"}]
     * ["tcp://[fe80::1]", {"uri": "tcp://[fe80::1]:3301", "host": "[fe80::1]", "port": 3301}]
     * ["tcp://[fe80::1]:1234", {"uri": "tcp://[fe80::1]:1234", "host": "[fe80::1]", "port": 1234}]
     * ["tcp://[de:ad:be:ef::ca:fe]:1234", {"uri": "tcp://[de:ad:be:ef::ca:fe]:1234", "host": "[de:ad:be:ef::ca:fe]", "port": 1234}]
     * ["tcp://foo@[de:ad:be:ef::ca:fe]:1234", {"uri": "tcp://[de:ad:be:ef::ca:fe]:1234", "host": "[de:ad:be:ef::ca:fe]", "port": 1234, "username": "foo", "password": ""}]
     */
    public function testParseValidTcpDsn(string $dsn, array $expected) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expected['uri'], $dsn->getConnectionUri());
        self::assertSame($expected['host'], $dsn->getHost());
        self::assertSame($expected['port'], $dsn->getPort());
        self::assertNull($dsn->getPath());
        self::assertTrue($dsn->isTcp());

        if (isset($expected['username'])) {
            self::assertSame($expected['username'], $dsn->getUsername());
            self::assertSame($expected['password'] ?? '', $dsn->getPassword());
        }
    }

    /**
     * @testWith
     * ["unix:///path/to/socket.sock", {"uri": "unix:///path/to/socket.sock", "path": "/path/to/socket.sock"}]
     * ["unix:///path/to/socket.sock?opt=42", {"uri": "unix:///path/to/socket.sock", "path": "/path/to/socket.sock"}]
     * ["unix://foo@/path/to/socket.sock", {"uri": "unix:///path/to/socket.sock", "path": "/path/to/socket.sock", "username": "foo", "password": ""}]
     * ["unix://foo:bar@/path/to/socket.sock", {"uri": "unix:///path/to/socket.sock", "path": "/path/to/socket.sock", "username": "foo", "password": "bar"}]
     * ["unix://foo:bar@/path/to/socket.sock?opt=42", {"uri": "unix:///path/to/socket.sock", "path": "/path/to/socket.sock", "username": "foo", "password": "bar"}]
     * ["unix://f%40%40:b%40r@%2fsocket.sock", {"uri": "unix://%2fsocket.sock", "path": "/socket.sock", "username": "f@@", "password": "b@r"}]
     */
    public function testParseValidUdpDsn(string $dsn, array $expected) : void
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

    /**
     * @testWith
     * [""]
     * ["foobar"]
     * ["tcp:"]
     * ["tcp:host"]
     * ["tcp:/"]
     * ["tcp:/host"]
     * ["tcp://"]
     * ["tcp://host/path"]
     * ["tcp:////"]
     * ["unix:"]
     * ["unix:/"]
     * ["unix:/path"]
     * ["unix://"]
     * ["unix://foo.bar:3030/path"]
     * ["unix:////"]
     * ["http://host"]
     * ["file:///path"]
     */
    public function testParseInvalidDsn(string $nonDsn) : void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Unable to parse DSN "%s".', $nonDsn));
        Dsn::parse($nonDsn);
    }

    /**
     * @testWith
     * ["tcp://host/?foo=bar", "foo", "bar"]
     * ["tcp://host/?foo=b%40r", "foo", "b@r"]
     * ["tcp://host/?foo=", "foo", ""]
     * ["tcp://host/?foo=42", "foo", "42"]
     * ["tcp://host/?foo=%25", "foo", "%"]
     * ["tcp://host/?foo=%2525", "foo", "%25"]
     * ["tcp://host", "foo", null]
     * ["unix:///socket.sock/?foo=bar", "foo", "bar"]
     * ["unix:///socket.sock/?foo=b%40r", "foo", "b@r"]
     * ["unix:///socket.sock/?foo=", "foo", ""]
     * ["unix:///socket.sock/?foo=42", "foo", "42"]
     * ["unix:///socket.sock/?foo=%25", "foo", "%"]
     * ["unix:///socket.sock/?foo=%2525", "foo", "%25"]
     * ["unix:///socket.sock", "foo", null]
     */
    public function testGetString(string $dsn, string $option, ?string $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getString($option));
    }

    public function testGetStringDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=bar');
        self::assertSame('qux', $dsn->getString('baz', 'qux'));
    }

    /**
     * @testWith
     * ["tcp://host/?foo=42", "foo", 42]
     * ["tcp://host/?foo=0", "foo", 0]
     * ["tcp://host", "foo", null]
     * ["unix:///socket.sock/?foo=42", "foo", 42]
     * ["unix:///socket.sock/?foo=0", "foo", 0]
     * ["unix:///socket.sock", "foo", null]
     */
    public function testGetInt(string $dsn, string $option, $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getInt($option));
    }

    public function testGetIntDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=2');
        self::assertSame(42, $dsn->getInt('baz', 42));
    }

    /**
     * @testWith
     * ["tcp://host/?foo=bar", "foo"]
     * ["tcp://host/?foo=4.2", "foo"]
     * ["tcp://host/?foo=true", "foo"]
     * ["tcp://host/?foo=", "foo"]
     * ["unix:///socket.sock/?foo=bar", "foo"]
     * ["unix:///socket.sock/?foo=4.2", "foo"]
     * ["unix:///socket.sock/?foo=true", "foo"]
     * ["unix:///socket.sock/?foo=", "foo"]
     */
    public function testGetNonInt(string $dsn, string $option) : void
    {
        $dsn = Dsn::parse($dsn);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('DSN option "foo" must be of the type int.');
        $dsn->getInt($option);
    }

    /**
     * @testWith
     * ["tcp://host/?foo=true", "foo", true]
     * ["tcp://host/?foo=false", "foo", false]
     * ["tcp://host/?foo=on", "foo", true]
     * ["tcp://host/?foo=off", "foo", false]
     * ["tcp://host/?foo=1", "foo", true]
     * ["tcp://host/?foo=0", "foo", false]
     * ["tcp://host/?foo=", "foo", false]
     * ["tcp://host", "foo", null]
     * ["unix:///socket.sock/?foo=true", "foo", true]
     * ["unix:///socket.sock/?foo=false", "foo", false]
     * ["unix:///socket.sock/?foo=on", "foo", true]
     * ["unix:///socket.sock/?foo=off", "foo", false]
     * ["unix:///socket.sock/?foo=1", "foo", true]
     * ["unix:///socket.sock/?foo=0", "foo", false]
     * ["unix:///socket.sock/?foo=", "foo", false]
     * ["unix:///socket.sock", "foo", null]
     */
    public function testGetBool(string $dsn, string $option, ?bool $expectedValue) : void
    {
        $dsn = Dsn::parse($dsn);
        self::assertSame($expectedValue, $dsn->getBool($option));
    }

    public function testGetBoolDefault() : void
    {
        $dsn = Dsn::parse('tcp://host/?foo=0');
        self::assertTrue($dsn->getBool('baz', true));
    }

    /**
     * @testWith
     * ["tcp://host/?foo=bar", "foo"]
     * ["tcp://host/?foo=42", "foo"]
     * ["unix:///socket.sock/?foo=bar", "foo"]
     * ["unix:///socket.sock/?foo=42", "foo"]
     */
    public function testGetNonBool(string $dsn, string $option) : void
    {
        $dsn = Dsn::parse($dsn);

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('DSN option "foo" must be of the type bool.');
        $dsn->getBool($option);
    }
}

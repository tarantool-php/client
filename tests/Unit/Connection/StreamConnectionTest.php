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

namespace Tarantool\Client\Tests\Unit\Connection;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Tests\Unit\OptionsProvider;

final class StreamConnectionTest extends TestCase
{
    #[DataProviderExternal(OptionsProvider::class, 'provideConnectionArrayOptionsOfValidTypes')]
    #[DataProviderExternal(OptionsProvider::class, 'provideTcpExtraConnectionArrayOptionsOfValidTypes')]
    #[DoesNotPerformAssertions]
    public function testCreateTcpAcceptsOptionOfValidType(string $optionName, $optionValue) : void
    {
        StreamConnection::createTcp(StreamConnection::DEFAULT_TCP_URI, [$optionName => $optionValue]);
    }

    #[DataProviderExternal(OptionsProvider::class, 'provideConnectionArrayOptionsOfValidTypes')]
    #[DoesNotPerformAssertions]
    public function testCreateUdsAcceptsOptionOfValidType(string $optionName, $optionValue) : void
    {
        StreamConnection::createUds('unix:///socket.sock', [$optionName => $optionValue]);
    }

    #[DataProviderExternal(OptionsProvider::class, 'provideConnectionArrayOptionsOfInvalidTypes')]
    #[DataProviderExternal(OptionsProvider::class, 'provideTcpExtraConnectionArrayOptionsOfInvalidTypes')]
    public function testCreateTcpRejectsOptionOfInvalidType(string $optionName, $optionValue, string $expectedType) : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches("/must be of(?: the)? type $expectedType/");

        StreamConnection::createTcp(StreamConnection::DEFAULT_TCP_URI, [$optionName => $optionValue]);
    }

    #[DataProviderExternal(OptionsProvider::class, 'provideConnectionArrayOptionsOfInvalidTypes')]
    public function testCreateUdsRejectsOptionOfInvalidType(string $optionName, $optionValue, string $expectedType) : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches("/must be of(?: the)? type $expectedType/");

        StreamConnection::createUds('unix:///socket.sock', [$optionName => $optionValue]);
    }
}

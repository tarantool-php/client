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

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Connection\StreamConnection;

final class StreamConnectionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(\Tarantool\Client\Tests\Unit\OptionsProvider::class, 'provideConnectionArrayOptionsOfValidTypes')]
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(\Tarantool\Client\Tests\Unit\OptionsProvider::class, 'provideTcpExtraConnectionArrayOptionsOfValidTypes')]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testCreateTcpAcceptsOptionOfValidType(string $optionName, $optionValue) : void
    {
        StreamConnection::createTcp(StreamConnection::DEFAULT_TCP_URI, [$optionName => $optionValue]);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(\Tarantool\Client\Tests\Unit\OptionsProvider::class, 'provideConnectionArrayOptionsOfValidTypes')]
    #[\PHPUnit\Framework\Attributes\DoesNotPerformAssertions]
    public function testCreateUdsAcceptsOptionOfValidType(string $optionName, $optionValue) : void
    {
        StreamConnection::createUds('unix:///socket.sock', [$optionName => $optionValue]);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(\Tarantool\Client\Tests\Unit\OptionsProvider::class, 'provideConnectionArrayOptionsOfInvalidTypes')]
    #[\PHPUnit\Framework\Attributes\DataProviderExternal(\Tarantool\Client\Tests\Unit\OptionsProvider::class, 'provideTcpExtraConnectionArrayOptionsOfInvalidTypes')]
    public function testCreateTcpRejectsOptionOfInvalidType(string $optionName, $optionValue, string $expectedType) : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches("/must be of(?: the)? type $expectedType/");

        StreamConnection::createTcp(StreamConnection::DEFAULT_TCP_URI, [$optionName => $optionValue]);
    }

    #[\PHPUnit\Framework\Attributes\DataProviderExternal(\Tarantool\Client\Tests\Unit\OptionsProvider::class, 'provideConnectionArrayOptionsOfInvalidTypes')]
    public function testCreateUdsRejectsOptionOfInvalidType(string $optionName, $optionValue, string $expectedType) : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches("/must be of(?: the)? type $expectedType/");

        StreamConnection::createUds('unix:///socket.sock', [$optionName => $optionValue]);
    }
}

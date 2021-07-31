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
use Tarantool\Client\Client;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Tests\PhpUnitCompat;

final class ClientFactoryTest extends TestCase
{
    use PhpUnitCompat;

    public function testFromDefaultsCreatesClientWithPurePacker() : void
    {
        $client = Client::fromDefaults();

        self::assertInstanceOf(PurePacker::class, $client->getHandler()->getPacker());
    }

    public function testFromOptionsCreatesClientWithPurePacker() : void
    {
        $client = Client::fromOptions(['uri' => 'tcp://tnt']);

        self::assertInstanceOf(PurePacker::class, $client->getHandler()->getPacker());
    }

    public function testFromOptionsCreatesClientWithCustomPacker() : void
    {
        $packer = $this->createMock(Packer::class);
        $client = Client::fromOptions(['uri' => 'tcp://tnt'], $packer);

        self::assertSame($packer, $client->getHandler()->getPacker());
    }

    public function testFromDsnCreatesClientWithPurePacker() : void
    {
        $client = Client::fromDsn('tcp://tnt');

        self::assertInstanceOf(PurePacker::class, $client->getHandler()->getPacker());
    }

    public function testFromDsnCreatesClientWithCustomPacker() : void
    {
        $packer = $this->createMock(Packer::class);
        $client = Client::fromDsn('tcp://tnt', $packer);

        self::assertSame($packer, $client->getHandler()->getPacker());
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\Unit\OptionsProvider::provideClientArrayOptionsOfValidTypes
     * @dataProvider \Tarantool\Client\Tests\Unit\OptionsProvider::provideTcpExtraConnectionArrayOptionsOfValidTypes
     * @doesNotPerformAssertions
     */
    public function testFromOptionsAcceptsOptionOfValidType(string $optionName, $optionValue, array $extraOptions = []) : void
    {
        Client::fromOptions([$optionName => $optionValue] + $extraOptions);
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\Unit\OptionsProvider::provideClientArrayOptionsOfInvalidTypes
     * @dataProvider \Tarantool\Client\Tests\Unit\OptionsProvider::provideTcpExtraConnectionArrayOptionsOfInvalidTypes
     */
    public function testFromOptionsRejectsOptionOfInvalidType(string $optionName, $optionValue, string $expectedType, array $extraOptions = []) : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches("/must be of(?: the)? type $expectedType/");

        Client::fromOptions([$optionName => $optionValue] + $extraOptions);
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\Unit\OptionsProvider::provideClientDsnOptionsOfValidTypes
     * @doesNotPerformAssertions
     */
    public function testFromDsnAcceptsOptionOfValidType(string $query) : void
    {
        Client::fromDsn("tcp://tnt/?$query");
    }

    /**
     * @dataProvider \Tarantool\Client\Tests\Unit\OptionsProvider::provideClientDsnOptionsOfInvalidTypes
     */
    public function testFromDsnRejectsOptionOfInvalidType(string $query, string $optionName, string $expectedType) : void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches("/\b$optionName\b.+?must be of(?: the)? type $expectedType/");

        Client::fromDsn("tcp://tnt/?$query");
    }
}

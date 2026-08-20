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

namespace Tarantool\Client\Tests\Integration\MessagePack;

use Decimal\Decimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnitExtras\Annotation\Attribute\Requires;
use Tarantool\Client\Client;
use Tarantool\Client\Packer\Extension\DecimalExtension;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;
use Tarantool\Client\Tests\PackerDataProvider;
use Tarantool\PhpUnit\Annotation\Attribute\Lua;

#[Lua('dec = require(\'decimal\').new(\'18446744073709551615\')')]
#[Lua('space = create_space(\'decimal_primary\')')]
#[Lua('space:format({{name = \'id\', type = \'decimal\'}})')]
#[Lua('space:create_index("primary", {parts = {1, \'decimal\'}})')]
#[Lua('space:insert({dec})')]
#[Requires('Tarantool', '>=2.3')]
#[RequiresPhpExtension('decimal')]
final class DecimalExtensionTest extends TestCase
{
    public const DECIMAL_BIG_INT = '18446744073709551615';
    private const TARANTOOL_DECIMAL_PRECISION = 38;

    public function testBinarySelectByDecimalKeySucceeds() : void
    {
        $client = self::createClientWithDecimalSupport();

        $decimal = Decimal::valueOf(self::DECIMAL_BIG_INT, self::TARANTOOL_DECIMAL_PRECISION);
        $space = $client->getSpace('decimal_primary');
        $result = $space->select(Criteria::key([$decimal]));

        self::assertTrue(isset($result[0][0]));
        self::assertEquals($decimal, $result[0][0]);
    }

    #[Requires('Tarantool', '>=2.10-stable')]
    public function testSqlSelectByDecimalKeySucceeds() : void
    {
        $client = self::createClientWithDecimalSupport();

        $decimal = Decimal::valueOf(self::DECIMAL_BIG_INT, self::TARANTOOL_DECIMAL_PRECISION);
        $result = $client->executeQuery('SELECT * FROM "decimal_primary" WHERE "id" = ?', $decimal);

        self::assertFalse($result->isEmpty());
        self::assertEquals($decimal, $result->getFirst()['id']);
    }

    #[DataProvider('provideDecimalStrings')]
    public function testLuaPackingAndUnpacking(string $decimalString) : void
    {
        $client = self::createClientWithDecimalSupport();

        [$decimal] = $client->evaluate('return require("decimal").new(...)', $decimalString);
        self::assertSame(
            self::normalizeDecimalString($decimalString),
            $decimal->toFixed(self::TARANTOOL_DECIMAL_PRECISION)
        );

        [$isEqual] = $client->evaluate(
            sprintf("return require('decimal').new('%s') == ...", $decimalString),
            Decimal::valueOf($decimalString, self::TARANTOOL_DECIMAL_PRECISION)
        );
        self::assertTrue($isEqual);
    }

    public static function provideDecimalStrings() : iterable
    {
        return [
            ['0'],
            ['-0'],
            ['42'],
            ['-127'],
            ['0.0'],
            ['00000.0000000'],
            ['00009.9000000'],
            ['1.000000099'],
            ['4.2'],
            ['1E-10'],
            ['-2E-15'],
            ['0.0000234'],
            [str_repeat('9', self::TARANTOOL_DECIMAL_PRECISION)],
            ['-'.str_repeat('9', self::TARANTOOL_DECIMAL_PRECISION)],
            ['0.'.str_repeat('1', self::TARANTOOL_DECIMAL_PRECISION)],
            [str_repeat('1', self::TARANTOOL_DECIMAL_PRECISION).'.0'],
            ['9.'.str_repeat('9', self::TARANTOOL_DECIMAL_PRECISION - 1)],
            [str_repeat('9', self::TARANTOOL_DECIMAL_PRECISION - 1).'.9'],
        ];
    }

    public function testBigIntegerUnpacksToDecimal() : void
    {
        $client = self::createClientWithDecimalSupport();
        [$number] = $client->evaluate('return 18446744073709551615ULL');

        self::assertInstanceOf(Decimal::class, $number);
        self::assertEquals(Decimal::valueOf('18446744073709551615'), $number);
    }

    #[DataProviderExternal(PackerDataProvider::class, 'providePurePackerWithDefaultSettings')]
    public function testPurePackerUnpacksBigIntToDecimal(PurePacker $packer) : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerFactory(static function () use ($packer) { return $packer; })
            ->build();

        [$number] = $client->evaluate(sprintf('return %sULL', self::DECIMAL_BIG_INT));

        self::assertInstanceOf(Decimal::class, $number);
        self::assertEquals(Decimal::valueOf(self::DECIMAL_BIG_INT), $number);
    }

    private static function createClientWithDecimalSupport() : Client
    {
        return ClientBuilder::createFromEnv()
            ->setPackerFactory(static function () {
                return PurePacker::fromExtensions(new DecimalExtension());
            })
            ->build();
    }

    private static function normalizeDecimalString(string $decimal) : string
    {
        return Decimal::valueOf($decimal, self::TARANTOOL_DECIMAL_PRECISION)->toFixed(self::TARANTOOL_DECIMAL_PRECISION);
    }
}

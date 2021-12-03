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
use Tarantool\Client\Client;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\Extension\DecimalExtension;
use Tarantool\Client\Packer\PackerFactory;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool >=2.3
 * @requires extension decimal
 * @requires clientPacker pure
 *
 * @lua dec = require('decimal').new('18446744073709551615')
 * @lua space = create_space('decimal_primary')
 * @lua space:format({{name = 'id', type = 'decimal'}})
 * @lua space:create_index("primary", {parts = {1, 'decimal'}})
 * @lua space:insert({dec})
 */
final class DecimalExtensionTest extends TestCase
{
    private const DECIMAL_BIG_INT = '18446744073709551615';
    private const TARANTOOL_DECIMAL_PRECISION = 38;

    public function testBinarySelectByDecimalKeySucceeds() : void
    {
        $client = self::createClientWithDecimalSupport();

        $decimal = new Decimal(self::DECIMAL_BIG_INT, self::TARANTOOL_DECIMAL_PRECISION);
        $space = $client->getSpace('decimal_primary');
        $result = $space->select(Criteria::key([$decimal]));

        self::assertTrue(isset($result[0][0]));
        self::assertTrue($decimal->equals($result[0][0]));
    }

    /**
     * @requires Tarantool >=2.10-stable
     */
    public function testSqlSelectByDecimalKeySucceeds() : void
    {
        $client = self::createClientWithDecimalSupport();

        $decimal = new Decimal(self::DECIMAL_BIG_INT, self::TARANTOOL_DECIMAL_PRECISION);
        $result = $client->executeQuery('SELECT * FROM "decimal_primary" WHERE "id" = ?', $decimal);

        self::assertFalse($result->isEmpty());
        self::assertTrue($decimal->equals($result->getFirst()['id']));
    }

    /**
     * @dataProvider provideDecimalStrings
     */
    public function testLuaPackingAndUnpacking(string $decimalString) : void
    {
        $client = self::createClientWithDecimalSupport();

        [$decimal] = $client->evaluate('return require("decimal").new(...)', $decimalString);
        self::assertTrue($decimal->equals($decimalString));

        [$isEqual] = $client->evaluate(
            sprintf("return require('decimal').new('%s') == ...", $decimalString),
            new Decimal($decimalString, self::TARANTOOL_DECIMAL_PRECISION)
        );
        self::assertTrue($isEqual);
    }

    public function provideDecimalStrings() : iterable
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
        self::assertTrue((new Decimal('18446744073709551615'))->equals($number));
    }

    public function testPackerFactorySetsBigIntAsDecUnpackOption() : void
    {
        $client = new Client(new DefaultHandler(
            ClientBuilder::createFromEnv()->createConnection(),
            PackerFactory::create()
        ));

        [$number] = $client->evaluate(sprintf('return %sULL', self::DECIMAL_BIG_INT));

        self::assertTrue((new Decimal(self::DECIMAL_BIG_INT))->equals($number));
    }

    private static function createClientWithDecimalSupport() : Client
    {
        return ClientBuilder::createFromEnv()
            ->setPackerPureFactory(static function () {
                return PurePacker::fromExtensions(new DecimalExtension());
            })
            ->build();
    }
}

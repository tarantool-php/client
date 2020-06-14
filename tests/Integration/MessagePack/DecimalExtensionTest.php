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
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool >=2.3
 * @requires extension decimal
 * @requires clientPacker pure
 */
final class DecimalExtensionTest extends TestCase
{
    private const TARANTOOL_DECIMAL_PRECISION = 38;

    /**
     * @lua dec = require('decimal')
     *
     * @dataProvider provideDecimalStrings
     */
    public function testPackingAndUnpacking(string $decimalString) : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerPureFactory(static function () {
                return PurePacker::fromExtensions(new DecimalExtension());
            })
            ->build();

        [$decimal] = $client->evaluate('return dec.new(...)', $decimalString);
        self::assertTrue($decimal->equals($decimalString));

        [$isEqual] = $client->evaluate(
            "return dec.new('$decimalString') == ...",
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
        [$number] = $this->client->evaluate('return 18446744073709551615ULL');

        self::assertTrue((new Decimal('18446744073709551615'))->equals($number));
    }

    public function testPackerFactorySetsBigIntAsDecUnpackOption() : void
    {
        $client = new Client(new DefaultHandler(
            ClientBuilder::createFromEnv()->createConnection(),
            PackerFactory::create()
        ));

        [$number] = $client->evaluate('return 18446744073709551615ULL');

        self::assertTrue((new Decimal('18446744073709551615'))->equals($number));
    }
}

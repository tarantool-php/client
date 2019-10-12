<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration\MessagePack;

use Decimal\Decimal;
use PHPUnit\Framework\Assert;
use Tarantool\Client\Packer\Extension\DecimalExtension;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class MessagePackTest extends TestCase
{
    private const TARANTOOL_DECIMAL_PRECISION = 38;

    /**
     * @dataProvider providePackUnpackData
     */
    public function testPackUnpack($arg) : void
    {
        self::assertSame([$arg], $this->client->evaluate('return ...', $arg));
    }

    public function providePackUnpackData() : iterable
    {
        return [
            [[]],
            [42],
            [-42],
            [4.2],
            [-4.2],
            [null],
            [false],
            ['string'],
            ["\x04\x00\xa0\x00\x00"],
            [[1, 2]],
            [[[[1, 2]]]],
            [['foo' => 'bar']],
            // User defined types (MessagePack extensions) are not yet supported:
            // https://github.com/tarantool/tarantool/issues/465
            // [[(object) ['foo' => 'bar']]],
        ];
    }

    public function testPackUnpackMultiDimensionalArray() : void
    {
        $array = [
            [
                'foo' => [42, 'a' => [null]],
                'bar' => [],
                10000 => -1,
            ],
            true,
        ];

        [$result] = $this->client->evaluate('return ...', $array);

        method_exists(Assert::class, 'assertEqualsCanonicalizing')
            ? self::assertEqualsCanonicalizing($array, $result)
            : self::assertEquals($array, $result, '', 0.0, 10, true);
    }

    /**
     * @eval space = create_space('custom_type')
     * @eval space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testCustomType() : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerPureFactory(static function () {
                return PurePacker::fromExtensions(new DateTimeExtension(42));
            })
            ->setPackerPeclFactory(static function () {
                return new PeclPacker(true);
            })
            ->build();

        // @see https://github.com/msgpack/msgpack-php/issues/137
        if (PHP_VERSION_ID >= 70400 && $client->getHandler()->getPacker() instanceof PeclPacker) {
            self::markTestSkipped('The msgpack extension does not pack objects correctly on PHP 7.4.');
        }

        $date = new \DateTimeImmutable();
        $space = $client->getSpace('custom_type');
        $result = $space->insert([100, $date]);

        self::assertEquals($date, $result[0][1]);
        self::assertEquals($date, $space->select(Criteria::key([100]))[0][1]);
    }

    /**
     * @requires Tarantool 2.3
     * @requires extension decimal
     * @requires function MessagePack\Packer::pack
     *
     * @eval dec = require('decimal')
     *
     * @dataProvider provideDecimalStrings
     */
    public function testDecimalType(string $decimalString) : void
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
}

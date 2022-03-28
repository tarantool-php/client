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

use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;
use Tarantool\Client\Tests\PhpUnitCompat;

final class MessagePackTest extends TestCase
{
    use PhpUnitCompat;

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

        self::assertEqualsCanonicalizing($array, $result);
    }

    /**
     * @lua space = create_space('custom_type')
     * @lua space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testCustomType() : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerFactory(static function () {
                return PurePacker::fromExtensions(new DateTimeExtension(42));
            })
            ->build();

        $date = new \DateTimeImmutable();
        $space = $client->getSpace('custom_type');
        $result = $space->insert([100, $date]);

        self::assertEquals($date, $result[0][1]);
        self::assertEquals($date, $space->select(Criteria::key([100]))[0][1]);
    }

    /**
     * @requires condition env.EXT_DISABLE_DECIMAL
     *
     * @dataProvider \Tarantool\Client\Tests\PackerDataProvider::providePurePackerWithDefaultSettings()
     */
    public function testPurePackerUnpacksBigIntToString(PurePacker $packer) : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerFactory(static function () use ($packer) { return $packer; })
            ->build();

        [$number] = $client->evaluate(sprintf('return %sULL', DecimalExtensionTest::DECIMAL_BIG_INT));

        self::assertSame(DecimalExtensionTest::DECIMAL_BIG_INT, $number);
    }
}

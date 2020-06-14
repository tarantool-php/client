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

use PHPUnit\Framework\Assert;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

final class MessagePackTest extends TestCase
{
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
     * @lua space = create_space('custom_type')
     * @lua space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
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
            self::markTestSkipped('The msgpack extension does not pack objects correctly on PHP 7.4');
        }

        $date = new \DateTimeImmutable();
        $space = $client->getSpace('custom_type');
        $result = $space->insert([100, $date]);

        self::assertEquals($date, $result[0][1]);
        self::assertEquals($date, $space->select(Criteria::key([100]))[0][1]);
    }

    /**
     * @requires clientPacker pure
     */
    public function testUnpackingBigIntegerAsString() : void
    {
        [$number] = $this->client->evaluate('return 18446744073709551615ULL');

        self::assertSame('18446744073709551615', $number);
    }
}

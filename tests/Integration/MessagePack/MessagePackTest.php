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

namespace Tarantool\Client\Tests\Integration\MessagePack;

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval create_fixtures()
 */
final class MessagePackTest extends TestCase
{
    /**
     * @dataProvider providePackUnpackData
     */
    public function testPackUnpack($arg) : void
    {
        $result = $this->client->evaluate('return func_arg(...)', $arg);

        self::assertSame([$arg], $result);
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
            true,
            [
                's' => [1, 1428578535],
                'u' => 1428578535,
                'v' => [],
                'c' => [
                    2 => [1, 1428578535],
                    106 => [1, 1428578535],
                ],
                'pc' => [
                    2 => [1, 1428578535, 9243],
                    106 => [1, 1428578535, 9243],
                ],
            ],
            true,
        ];

        $result = $this->client->evaluate('return func_arg(...)', $array);

        self::assertEquals([$array], $result, '', 0.0, 5, true);
    }

    public function testStoringCustomTypeInTuple() : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerPureFactory(function () {
                return new PurePacker(
                    (new Packer())->registerTransformer($t = new DateTimeTransformer(42)),
                    (new BufferUnpacker())->registerTransformer($t)
                );
            })
            ->setPackerPeclFactory(function () {
                return new PeclPacker(true);
            })
            ->build();

        $date = new \DateTimeImmutable();
        $space = $client->getSpace('space_misc');
        $result = $space->insert([100, 'now', $date]);

        self::assertEquals($date, $result[0][2]);
        self::assertEquals($date, $space->select([100])[0][2]);
    }
}

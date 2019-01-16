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

namespace Tarantool\Client\Tests\Integration;

/**
 * @eval create_fixtures()
 */
final class MessagePackTest extends TestCase
{
    /**
     * @dataProvider providePackUnpackData
     */
    public function testPackUnpack($data) : void
    {
        $result = $this->client->evaluate('return func_arg(...)', [$data]);

        self::assertSame([$data], $result);
    }

    public function providePackUnpackData() : iterable
    {
        return [
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
            // Object serialization is not yet supported: https://github.com/tarantool/tarantool/issues/465
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

        $result = $this->client->evaluate('return func_arg(...)', [$array]);

        self::assertEquals([$array], $result, '', 0.0, 5, true);
    }
}
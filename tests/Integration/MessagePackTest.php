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

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Tests\Integration\MessagePack\StdClassTransformer;

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
        $response = $this->client->evaluate('return func_arg(...)', [$data]);

        self::assertSame([$data], $response->getData());
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

        $response = $this->client->evaluate('return func_arg(...)', [$array]);

        self::assertEquals([$array], $response->getData(), '', 0.0, 5, true);
    }

    public function testPackUnpackObject() : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerPureFactory(function () {
                return new PurePacker(
                    (new Packer())->registerTransformer(new StdClassTransformer(42)),
                    (new BufferUnpacker())->registerTransformer(new StdClassTransformer(42))
                );
            })
            ->build();

        if ($client->getPacker() instanceof PurePacker) {
            self::markTestSkipped('Object serialization is not yet supported for the PurePacker (see https://github.com/tarantool/tarantool/issues/465)');
        }

        // PECL: bin2hex($this->packer->pack($body)) = 8227b472657475726e2066756e635f617267282e2e2e29219182c0a8737464436c617373a3666f6fa3626172

        $obj = (object) ['foo' => 'bar'];
        $response = $client->evaluate('return func_arg(...)', [$obj]);

        self::assertEquals([$obj], $response->getData());
    }
}

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

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\Exception;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @eval create_fixtures()
 */
final class UpdateTest extends TestCase
{
    /**
     * @dataProvider provideUpdateData
     */
    public function testUpdate(array $operations, array $result) : void
    {
        $space = $this->client->getSpace('space_data');
        $response = $space->update([1], $operations);

        self::assertSame([$result], $response->getData());
    }

    public function provideUpdateData() : iterable
    {
        return [
            [
                [['+', 1, 16], ['=', 3, 98], ['=', 4, 0x11111]],
                [1, 18, 'tuple_1', 98, 0x11111],
            ],
            [
                [['-', 3, 10], ['&', 4, 0x10101]],
                [1, 18, 'tuple_1', 88, 0x10101],
            ],
            [
                [['^', 4, 0x11100]],
                [1, 18, 'tuple_1', 88, 0x01001],
            ],
            [
                [['^', 4, 0x00010]],
                [1, 18, 'tuple_1', 88, 0x01011],
            ],
            [
                [[':', 2, 2, 1, 'uup']],
                [1, 18, 'tuuuple_1', 88, 0x01011],
            ]
        ];
    }

    public function testUpdateWithIndexName() : void
    {
        $space = $this->client->getSpace('space_data');

        self::assertSame([2, 4, 'tuple_2'], $space->select([2])->getData()[0]);

        $response = $space->update([2], [[':', 2, 0, 1, 'T']], 'primary');

        self::assertSame([[2, 4, 'Tuple_2']], $response->getData());
    }

    public function testUpdateWithNonExistingIndexName() : void
    {
        $space = $this->client->getSpace('space_data');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No index 'non_existing_index' is defined in space #".$space->getId());

        $space->update([2], [[':', 2, 0, 1, 'T']], 'non_existing_index');
    }

    public function testUpdateByNonExistingKey() : void
    {
        $space = $this->client->getSpace('space_misc');
        $response = $space->update([42], [['=', 2, 'qux']]);

        self::assertSame([], $response->getData());
    }

    /**
     * @dataProvider provideInvalidUpdateData
     */
    public function testUpdateUsingInvalidArgs(array $operation, string $errorMessage, int $errorCode) : void
    {
        $space = $this->client->getSpace('space_data');

        try {
            $space->update([1], [$operation]);
            $this->fail();
        } catch (Exception $e) {
            self::assertSame($errorMessage, $e->getMessage());
            self::assertSame($errorCode, $e->getCode());
        }
    }

    public function provideInvalidUpdateData() : iterable
    {
        $data = [
            [['+', 2, 1], "Argument type in operation '+' on field 2 does not match field type: expected a number", 26],
            [['', 2, 2], 'Unknown UPDATE operation', 28],
            [['bad_op', 2, 2], 'Unknown UPDATE operation', 28],
            [[':', 2, 2, 2, '', 'extra'], 'Unknown UPDATE operation', 28],
            [[], 'Illegal parameters, update operation must be an array {op,..}, got empty array', 1],
        ];

        return $data;
    }
}

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
final class InsertTest extends TestCase
{
    /**
     * @dataProvider provideInsertData
     */
    public function testInsert(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);
        $response = $space->insert($values);

        self::assertSame([$values], $response->getData());
    }

    public function provideInsertData() : iterable
    {
        return [
            ['space_str', ['']],
            ['space_str', ['foo']],
            ['space_str', ['null', null, null]],
            ['space_str', ['int', 42, -42]],
            ['space_str', ['float', 4.2, -4.2]],
            ['space_str', ['array', ['foo' => 'bar']]],
            ['space_num', [42]],
        ];
    }

    /**
     * @dataProvider provideInsertDataWithMismatchedTypes
     */
    public function testInsertTypeMismatchedValues(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/Tuple field 1 type does not match one required by operation: expected .+/');
        $this->expectExceptionCode(23);

        $space->insert($values);
    }

    public function provideInsertDataWithMismatchedTypes() : iterable
    {
        return [
            ['space_str', [null]],
            ['space_str', [42]],
            ['space_str', [[]]],
            ['space_num', [null]],
            ['space_num', [-42]],
            ['space_num', [4.2]],
            ['space_num', [[]]],
        ];
    }

    public function testInsertDuplicateKey() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Duplicate key exists in unique index 'primary' in space 'space_misc'");
        $this->expectExceptionCode(3);

        $space->insert([1, 'bazqux']);
    }
}

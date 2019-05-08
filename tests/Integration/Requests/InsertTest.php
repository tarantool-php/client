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

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Tests\Integration\TestCase;

final class InsertTest extends TestCase
{
    /**
     * @dataProvider provideInsertData
     *
     * @eval create_space('request_insert_str'):create_index('primary', {type = 'hash', parts = {1, 'str'}})
     * @eval create_space('request_insert_num'):create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testInsert(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);
        $result = $space->insert($values);

        self::assertSame([$values], $result);
    }

    public function provideInsertData() : iterable
    {
        return [
            ['request_insert_str', ['']],
            ['request_insert_str', ['foo']],
            ['request_insert_str', ['null', null, null]],
            ['request_insert_str', ['int', 42, -42]],
            ['request_insert_str', ['float', 4.2, -4.2]],
            ['request_insert_str', ['array', ['foo' => 'bar']]],
            ['request_insert_num', [42]],
        ];
    }

    /**
     * @dataProvider provideInsertDataWithMismatchedTypes
     *
     * @eval create_space('request_insert_str'):create_index('primary', {type = 'hash', parts = {1, 'str'}})
     * @eval create_space('request_insert_num'):create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testInsertTypeMismatchedValues(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessageRegExp('/Tuple field 1 type does not match one required by operation: expected .+/');
        $this->expectExceptionCode(23);

        $space->insert($values);
    }

    public function provideInsertDataWithMismatchedTypes() : iterable
    {
        return [
            ['request_insert_str', [null]],
            ['request_insert_str', [42]],
            ['request_insert_str', [[]]],
            ['request_insert_num', [null]],
            ['request_insert_num', [-42]],
            ['request_insert_num', [4.2]],
            ['request_insert_num', [[]]],
        ];
    }

    /**
     * @eval space = create_space('request_insert')
     * @eval space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     * @eval space:insert{1, 'foobar'}
     */
    public function testInsertDuplicateKey() : void
    {
        $space = $this->client->getSpace('request_insert');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("Duplicate key exists in unique index 'primary' in space 'request_insert'");
        $this->expectExceptionCode(3);

        $space->insert([1, 'bazqux']);
    }
}

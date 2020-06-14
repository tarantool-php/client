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

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Tests\Integration\TestCase;

final class InsertTest extends TestCase
{
    /**
     * @dataProvider provideInsertData
     *
     * @lua create_space('request_insert_str'):create_index('primary', {type = 'hash', parts = {1, 'str'}})
     * @lua create_space('request_insert_num'):create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
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
     * @lua create_space('request_insert_str'):create_index('primary', {type = 'hash', parts = {1, 'str'}})
     * @lua create_space('request_insert_num'):create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testInsertTypeMismatchedValues(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(23); // ER_FIELD_TYPE

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
     * @lua space = create_space('request_insert_dup_key')
     * @lua space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     * @lua space:insert{1, 'foobar'}
     */
    public function testInsertDuplicateKey() : void
    {
        $space = $this->client->getSpace('request_insert_dup_key');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage("Duplicate key exists in unique index 'primary' in space 'request_insert_dup_key'");

        $space->insert([1, 'bazqux']);
    }

    /**
     * @lua space = create_space('request_insert_empty_tuple')
     * @lua space:create_index('primary', {type = 'hash', parts = {1, 'unsigned'}})
     */
    public function testInsertEmptyTuple() : void
    {
        $space = $this->client->getSpace('request_insert_empty_tuple');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(39); // ER_FIELD_MISSING

        $space->insert([]);
    }
}

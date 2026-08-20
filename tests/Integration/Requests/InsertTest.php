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
use Tarantool\PhpUnit\Annotation\Attribute\Lua;

final class InsertTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideInsertData')]
    #[Lua('create_space(\'request_insert_str\'):create_index(\'primary\', {type = \'hash\', parts = {1, \'str\'}})')]
    #[Lua('create_space(\'request_insert_num\'):create_index(\'primary\', {type = \'hash\', parts = {1, \'unsigned\'}})')]
    public function testInsert(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);
        $result = $space->insert($values);

        self::assertSame([$values], $result);
    }

    public static function provideInsertData() : iterable
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

    #[\PHPUnit\Framework\Attributes\DataProvider('provideInsertDataWithMismatchedTypes')]
    #[Lua('create_space(\'request_insert_str\'):create_index(\'primary\', {type = \'hash\', parts = {1, \'str\'}})')]
    #[Lua('create_space(\'request_insert_num\'):create_index(\'primary\', {type = \'hash\', parts = {1, \'unsigned\'}})')]
    public function testInsertTypeMismatchedValues(string $spaceName, array $values) : void
    {
        $space = $this->client->getSpace($spaceName);

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(23); // ER_FIELD_TYPE

        $space->insert($values);
    }

    public static function provideInsertDataWithMismatchedTypes() : iterable
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

    #[Lua('space = create_space(\'request_insert_dup_key\')')]
    #[Lua('space:create_index(\'primary\', {type = \'hash\', parts = {1, \'unsigned\'}})')]
    #[Lua('space:insert{1, \'foobar\'}')]
    public function testInsertDuplicateKey() : void
    {
        $space = $this->client->getSpace('request_insert_dup_key');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessageMatches('/^Duplicate key exists in unique index ("|\')primary\1 in space ("|\')request_insert_dup_key\2/');

        $space->insert([1, 'bazqux']);
    }

    #[Lua('space = create_space(\'request_insert_empty_tuple\')')]
    #[Lua('space:create_index(\'primary\', {type = \'hash\', parts = {1, \'unsigned\'}})')]
    public function testInsertEmptyTuple() : void
    {
        $space = $this->client->getSpace('request_insert_empty_tuple');

        $this->expectException(RequestFailed::class);
        $this->expectExceptionCode(39); // ER_FIELD_MISSING

        $space->insert([]);
    }
}

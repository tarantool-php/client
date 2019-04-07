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

use Tarantool\Client\Tests\Integration\TestCase;

final class ExecuteTest extends TestCase
{
    /**
     * @beforeClass
     */
    public static function ensureSqlSupport() : void
    {
        if (self::matchTarantoolVersion('<2.0.0', $currentVersion)) {
            self::markTestSkipped(sprintf('This version of Tarantool (%s) does not support sql.', $currentVersion));
        }
    }

    /**
     * @dataProvider provideExecuteUpdateData
     */
    public function testExecuteUpdate(string $sql, array $params, $expectedCount, ?array $expectedAutoincrementIds) : void
    {
        $result = $this->client->executeUpdate($sql, ...$params);

        is_array($expectedCount)
            ? self::assertContains($result->count(), $expectedCount)
            : self::assertSame($expectedCount, $result->count())
        ;

        self::assertSame($expectedAutoincrementIds, $result->getAutoincrementIds());
    }

    public function provideExecuteUpdateData() : iterable
    {
        return [
            ['DROP TABLE IF EXISTS table_exec', [], [0, 1], null],
            ['CREATE TABLE table_exec (column1 INTEGER PRIMARY KEY AUTOINCREMENT, column2 VARCHAR(100))', [], 1, null],
            ['INSERT INTO table_exec VALUES (1, :val1), (2, :val2)', [[':val1' => 'A'], [':val2' => 'B']], 2, null],
            ['UPDATE table_exec SET column2 = ? WHERE column1 = 2', ['BB'], 1, null],
            ["INSERT INTO table_exec VALUES (100, 'a'), (null, 'b'), (120, 'c'), (null, 'd')", [], 4, [101, 121]],
        ];
    }

    /**
     * @dataProvider provideExecuteQueryData
     *
     * @depends testExecuteUpdate
     */
    public function testExecuteQuery(string $sql, array $params, array $expectedData) : void
    {
        $result = $this->client->executeQuery($sql, ...$params);

        self::assertSame($expectedData, $result->getData());
    }

    public function provideExecuteQueryData() : iterable
    {
        return [
            ['SELECT * FROM table_exec WHERE column1 = 1', [], [[1, 'A']]],
            ['SELECT column2 FROM table_exec WHERE column1 = 1', [], [['A']]],
            ['SELECT * FROM table_exec WHERE column1 = 2', [], [[2, 'BB']]],
            ['SELECT * FROM table_exec WHERE column1 = 3', [], []],
        ];
    }
}

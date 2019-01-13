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
    public function testExecuteUpdate(string $sql, array $params, $expectedResult) : void
    {
        $result = $this->client->executeUpdate($sql, $params);

        is_array($expectedResult)
            ? self::assertContains($result, $expectedResult)
            : self::assertSame($expectedResult, $result)
        ;
    }

    public function provideExecuteUpdateData() : iterable
    {
        return [
            ['DROP TABLE IF EXISTS table_exec', [], [0, 1]],
            ['CREATE TABLE table_exec (column1 INTEGER PRIMARY KEY, column2 VARCHAR(100))', [], 1],
            ['INSERT INTO table_exec VALUES (1, :val1), (2, :val2)', [[':val1' => 'A'], [':val2' => 'B']], 2],
            ['UPDATE table_exec SET column2 = ? WHERE column1 = 2', ['BB'], 1],
        ];
    }

    /**
     * @dataProvider provideExecuteQueryData
     *
     * @depends testExecuteUpdate
     */
    public function testExecuteQuery(string $sql, array $params, $expectedResult) : void
    {
        $result = $this->client->executeQuery($sql, $params);

        self::assertSame($expectedResult, $result->getData());
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

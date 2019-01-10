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
     * @dataProvider provideExecuteUpdateData
     */
    public function testExecuteUpdate(string $sql, array $params, $result) : void
    {
        if (version_compare($ver = $this->getTarantoolVersion(), '2.0.0', '<')) {
            self::markTestSkipped(sprintf('This version of Tarantool (%s) does not support sql.', $ver));
        }

        $response = $this->client->executeUpdate($sql, $params);

        is_array($result)
            ? self::assertContains($response->getRowCount(), $result)
            : self::assertSame($result, $response->getRowCount())
        ;
    }

    public function provideExecuteUpdateData() : iterable
    {
        return [
            ['DROP TABLE IF EXISTS table1', [], [0, 1]],
            ['CREATE TABLE table1 (column1 INTEGER PRIMARY KEY, column2 VARCHAR(100))', [], 1],
            ['INSERT INTO table1 VALUES (1, :val1), (2, :val2)', [[':val1' => 'A'], [':val2' => 'B']], 2],
            ['UPDATE table1 SET column2 = ? WHERE column1 = 2', ['BB'], 1],
        ];
    }

    /**
     * @dataProvider provideExecuteQueryData
     *
     * @depends testExecuteUpdate
     */
    public function testExecuteQuery(string $sql, array $params, $result) : void
    {
        if (version_compare($ver = $this->getTarantoolVersion(), '2.0.0', '<')) {
            self::markTestSkipped(sprintf('This version of Tarantool (%s) does not support sql.', $ver));
        }

        $response = $this->client->executeQuery($sql, $params);

        self::assertSame($result, $response->getData());
    }

    public function provideExecuteQueryData() : iterable
    {
        return [
            ['SELECT * FROM table1 WHERE column1 = 1', [], [[1, 'A']]],
            ['SELECT column2 FROM table1 WHERE column1 = 1', [], [['A']]],
            ['SELECT * FROM table1 WHERE column1 = 2', [], [[2, 'BB']]],
            ['SELECT * FROM table1 WHERE column1 = 3', [], []],
        ];
    }
}

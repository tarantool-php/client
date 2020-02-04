<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool 2
 *
 * @eval box.execute([[ DROP TABLE IF EXISTS exec_query ]])
 * @eval box.execute([[ CREATE TABLE exec_query (id INTEGER PRIMARY KEY, name VARCHAR(50)) ]])
 * @eval box.execute([[ INSERT INTO exec_query VALUES (1, 'A'), (2, 'B') ]])
 */
final class ExecuteTest extends TestCase
{
    /**
     * @eval box.execute([[ DROP TABLE IF EXISTS exec_update ]])
     * @eval box.execute([[ CREATE TABLE exec_update (id INTEGER PRIMARY KEY, name VARCHAR(50)) ]])
     */
    public function testExecuteUpdateInsertsRows() : void
    {
        $result = $this->client->executeUpdate(
            'INSERT INTO exec_update VALUES (1, :name1), (2, :name2)',
            [':name1' => 'A'], [':name2' => 'B']
        );

        self::assertNull($result->getAutoincrementIds());
        self::assertSame(2, $result->count());
    }

    /**
     * @eval box.execute([[ DROP TABLE IF EXISTS exec_update ]])
     * @eval box.execute([[ CREATE TABLE exec_update (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50)) ]])
     */
    public function testExecuteUpdateInsertsRowsWithAutoIncrementedIds() : void
    {
        $result = $this->client->executeUpdate("INSERT INTO exec_update VALUES (100, 'A'), (null, 'B'), (120, 'C'), (null, 'D')");

        self::assertSame([101, 121], $result->getAutoincrementIds());
        self::assertSame(4, $result->count());
    }

    /**
     * @eval box.execute([[ DROP TABLE IF EXISTS exec_update ]])
     * @eval box.execute([[ CREATE TABLE exec_update (id INTEGER PRIMARY KEY, name VARCHAR(50)) ]])
     * @eval box.execute([[ INSERT INTO exec_update VALUES (1, 'A'), (2, 'B') ]])
     */
    public function testExecuteUpdateUpdatesRow() : void
    {
        $result = $this->client->executeUpdate('UPDATE exec_update SET name = ? WHERE id = ?', 'BB', 2);

        self::assertNull($result->getAutoincrementIds());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryFetchesAllRows() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query');

        self::assertSame([[1, 'A'], [2, 'B']], $result->getData());
        self::assertSame(2, $result->count());
    }

    public function testExecuteQueryFetchesOneRow() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id = 1');

        self::assertSame([[1, 'A']], $result->getData());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryFetchesNoRows() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id = -1');

        self::assertSame([], $result->getData());
        self::assertSame(0, $result->count());
    }

    public function testExecuteQueryBindsPositionalParameters() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id = ? AND name = ?', 2, 'B');

        self::assertSame([[2, 'B']], $result->getData());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryBindsNamedParameters() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id = :id AND name = :name', [':name' => 'B'], [':id' => 2]);

        self::assertSame([[2, 'B']], $result->getData());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryBindsMixedParameters() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id = ? AND name = :name', 2, [':name' => 'B']);

        // this doesn't work, see https://github.com/tarantool/doc/issues/1096
        // $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id = :id AND name = ?', 'B', [':id' => 2]);

        self::assertSame([[2, 'B']], $result->getData());
        self::assertSame(1, $result->count());
    }
}

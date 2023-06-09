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

use Tarantool\Client\Keys;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool >=2
 *
 * @sql DROP TABLE IF EXISTS exec_query
 * @sql CREATE TABLE exec_query (id INTEGER PRIMARY KEY, name VARCHAR(50))
 * @sql INSERT INTO exec_query VALUES (1, 'A'), (2, 'B')
 */
final class ExecuteTest extends TestCase
{
    /**
     * @sql DROP TABLE IF EXISTS exec_update
     * @sql CREATE TABLE exec_update (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50))
     */
    public function testExecuteInsertsRows() : void
    {
        $response = $this->client->execute(
            'INSERT INTO exec_update VALUES (5, :name1), (null, :name2)',
            [':name1' => 'A'], [':name2' => 'B']
        );

        $expectedSqlInfo = [
            Keys::SQL_INFO_ROW_COUNT => 2,
            Keys::SQL_INFO_AUTO_INCREMENT_IDS => [6],
        ];

        self::assertSame($expectedSqlInfo, $response->getBodyField(Keys::SQL_INFO));
    }

    public function testExecuteFetchesAllRows() : void
    {
        $response = $this->client->execute('SELECT * FROM exec_query WHERE id > 0');

        self::assertSame([[1, 'A'], [2, 'B']], $response->getBodyField(Keys::DATA));
    }

    /**
     * @sql DROP TABLE IF EXISTS exec_update
     * @sql CREATE TABLE exec_update (id INTEGER PRIMARY KEY, name VARCHAR(50))
     */
    public function testExecuteUpdateInsertsRows() : void
    {
        $result = $this->client->executeUpdate(
            'INSERT INTO exec_update VALUES (1, :name1), (2, :name2)',
            [':name1' => 'A'], [':name2' => 'B']
        );

        self::assertSame([], $result->getAutoincrementIds());
        self::assertSame(2, $result->count());
    }

    /**
     * @sql DROP TABLE IF EXISTS exec_update
     * @sql CREATE TABLE exec_update (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50))
     */
    public function testExecuteUpdateInsertsRowsWithAutoIncrementedIds() : void
    {
        $result = $this->client->executeUpdate("INSERT INTO exec_update VALUES (100, 'A'), (null, 'B'), (120, 'C'), (null, 'D')");

        self::assertSame([101, 121], $result->getAutoincrementIds());
        self::assertSame(4, $result->count());
    }

    /**
     * @sql DROP TABLE IF EXISTS exec_update
     * @sql CREATE TABLE exec_update (id INTEGER PRIMARY KEY, name VARCHAR(50))
     * @sql INSERT INTO exec_update VALUES (1, 'A'), (2, 'B')
     */
    public function testExecuteUpdateUpdatesRow() : void
    {
        $result = $this->client->executeUpdate('UPDATE exec_update SET name = ? WHERE id = ?', 'BB', 2);

        self::assertSame([], $result->getAutoincrementIds());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryFetchesAllRows() : void
    {
        $result = $this->client->executeQuery('SELECT * FROM exec_query WHERE id > 0');

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
        $result = $this->client->executeQuery('SELECT ?, ?', 2, 'B');

        self::assertSame([[2, 'B']], $result->getData());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryBindsNamedParameters() : void
    {
        $result = $this->client->executeQuery('SELECT :id, :name', [':name' => 'B'], [':id' => 2]);

        self::assertSame([[2, 'B']], $result->getData());
        self::assertSame(1, $result->count());
    }

    public function testExecuteQueryBindsMixedParameters() : void
    {
        $result = $this->client->executeQuery('SELECT ?, :name', 2, [':name' => 'B']);

        self::assertSame([[2, 'B']], $result->getData());
        self::assertSame(1, $result->count());
    }

    /**
     * @see https://github.com/tarantool/tarantool/issues/4782
     */
    public function testExecuteQueryBindsMixedParametersAndSubstitutesPositionalOnes() : void
    {
        $result = $this->client->executeQuery('SELECT :id, ?', 'B', [':id' => 2]);

        self::assertSame([[2, null]], $result->getData());
        self::assertSame(1, $result->count());
    }

    public function testSqlQueryResultHoldsMetadata() : void
    {
        $client = ClientBuilder::createFromEnv()->build();

        $response = $client->executeQuery('SELECT * FROM exec_query WHERE id > 0');

        self::assertSame([[
            Keys::METADATA_FIELD_NAME => 'ID',
            Keys::METADATA_FIELD_TYPE => 'integer',
        ], [
            Keys::METADATA_FIELD_NAME => 'NAME',
            Keys::METADATA_FIELD_TYPE => 'string',
        ]], $response->getMetadata());
    }

    /**
     * @requires Tarantool >=2.6
     *
     * @sql DROP TABLE IF EXISTS %target_method%
     * @sql CREATE TABLE %target_method% (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50) COLLATE "unicode_ci")
     */
    public function testSqlQueryResultHoldsExtendedMetadata() : void
    {
        $client = ClientBuilder::createFromEnv()->build();
        $client->execute('SET SESSION "sql_full_metadata" = true');

        $tableName = $this->resolvePlaceholders('%target_method%');
        $response = $client->executeQuery("SELECT id, name AS full_name FROM $tableName WHERE id > 0");

        self::assertSame([[
            Keys::METADATA_FIELD_NAME => 'ID',
            Keys::METADATA_FIELD_TYPE => 'integer',
            Keys::METADATA_FIELD_IS_NULLABLE => false,
            Keys::METADATA_FIELD_IS_AUTOINCREMENT => true,
            Keys::METADATA_FIELD_SPAN => 'id',
        ], [
            Keys::METADATA_FIELD_NAME => 'FULL_NAME',
            Keys::METADATA_FIELD_TYPE => 'string',
            Keys::METADATA_FIELD_COLL => 'unicode_ci',
            Keys::METADATA_FIELD_IS_NULLABLE => true,
            Keys::METADATA_FIELD_SPAN => 'name',
        ]], $response->getMetadata());
    }
}

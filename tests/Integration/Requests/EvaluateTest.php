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

/**
 * @eval create_fixtures()
 */
final class EvaluateTest extends TestCase
{
    /**
     * @dataProvider provideEvaluateData
     */
    public function testEvaluate(array $args, $expectedResult) : void
    {
        self::assertSame($expectedResult, $this->client->evaluate(...$args));
    }

    public function provideEvaluateData() : iterable
    {
        return [
            [['return func_foo()'], [['foo' => 'foo', 'bar' => 42]]],
            [['return func_sum(...)', [42, -24]], [18]],
            [['return func_arg(...)', [[[42]]]], [[[42]]]],
            [['return func_arg(...)', [[42]]], [[42]]],
        ];
    }

    /**
     * @dataProvider provideEvaluateSqlData
     */
    public function testEvaluateSql(string $expr, array $expectedResult) : void
    {
        if (self::matchTarantoolVersion('<2.0.0', $currentVersion)) {
            self::markTestSkipped(sprintf('This version of Tarantool (%s) does not support sql.', $currentVersion));
        }

        self::assertSame($expectedResult, $this->client->evaluate($expr));
    }

    public function provideEvaluateSqlData() : iterable
    {
        return [
            ['return box.sql.execute([[DROP TABLE IF EXISTS table_eval]])', []],
            ['return box.sql.execute([[CREATE TABLE table_eval (column1 INTEGER PRIMARY KEY, column2 VARCHAR(100))]])', []],
            ["return box.sql.execute([[INSERT INTO table_eval VALUES (1, 'foo'), (2, 'bar')]])", []],
            ['return box.sql.execute([[SELECT * FROM table_eval]])', [[
                [1, 'foo'],
                [2, 'bar'],
            ]]],
        ];
    }
}

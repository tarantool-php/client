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
    public function testEvaluate(array $args, $result) : void
    {
        $response = $this->client->evaluate(...$args);

        self::assertSame($result, $response->getData());
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
}

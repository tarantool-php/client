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
final class CallTest extends TestCase
{
    /**
     * @dataProvider provideCallData
     */
    public function testCall(string $funcName, array $args, $return) : void
    {
        $response = $this->client->call($funcName, $args);

        self::assertSame($return, $response->getData()[0]);
    }

    public function provideCallData() : iterable
    {
        yield [
            'func' => 'func_foo',
            'args' => [],
            'ret' => ['foo' => 'foo', 'bar' => 42],
        ];

        yield [
            'func' => 'func_sum',
            'args' => [42, -24],
            'ret' => 18,
        ];

        yield [
            'func' => 'func_arg',
            'args' => [[42]],
            'ret' => [42],
        ];

        yield [
            'func' => 'func_arg',
            'args' => [[[42]]],
            'ret' => [[42]],
        ];

        yield [
            'func' => 'func_arg',
            'args' => [null],
            'ret' => null,
        ];
    }
}

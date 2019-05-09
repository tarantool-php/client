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

namespace Tarantool\Client\Tests\Unit\Schema;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Schema\Operations;

final class OperationsTest extends TestCase
{
    /**
     * @dataProvider provideOperationsData
     */
    public function testOperations(array $resultArray, Operations $operations) : void
    {
        self::assertSame($resultArray, $operations->toArray());
    }

    public function provideOperationsData() : iterable
    {
        return [
            [[['+', 1, 5], ['+', 2, 7]], Operations::add(1, 5)->andAdd(2, 7)],
            [[['-', 1, 5], ['-', 2, 7]], Operations::subtract(1, 5)->andSubtract(2, 7)],
            [[['&', 1, 5], ['&', 2, 7]], Operations::bitAnd(1, 5)->andBitAnd(2, 7)],
            [[['|', 1, 5], ['|', 2, 7]], Operations::bitOr(1, 5)->andBitOr(2, 7)],
            [[['^', 1, 5], ['^', 2, 7]], Operations::bitXor(1, 5)->andBitXor(2, 7)],
            [[[':', 1, 0, 5, 'foo'], [':', 2, 1, 7, 'bar']], Operations::splice(1, 0, 5, 'foo')->andSplice(2, 1, 7, 'bar')],
            [[['!', 1, 5], ['!', 2, 7]], Operations::insert(1, 5)->andInsert(2, 7)],
            [[['#', 1, 5], ['#', 2, 7]], Operations::delete(1, 5)->andDelete(2, 7)],
            [[['=', 1, 5], ['=', 2, 7]], Operations::set(1, 5)->andSet(2, 7)],
        ];
    }
}

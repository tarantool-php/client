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
    public function testAdd() : void
    {
        self::assertSame([['+', 1, 5], ['+', 2, 7]], Operations::add(1, 5)->andAdd(2, 7)->toArray());
    }

    public function testSubtract() : void
    {
        self::assertSame([['-', 1, 5], ['-', 2, 7]], Operations::subtract(1, 5)->andSubtract(2, 7)->toArray());
    }

    public function testAnd() : void
    {
        self::assertSame([['&', 1, 5], ['&', 2, 7]], Operations::bitAnd(1, 5)->andBitAnd(2, 7)->toArray());
    }

    public function testOr() : void
    {
        self::assertSame([['|', 1, 5], ['|', 2, 7]], Operations::bitOr(1, 5)->andBitOr(2, 7)->toArray());
    }

    public function testXor() : void
    {
        self::assertSame([['^', 1, 5], ['^', 2, 7]], Operations::bitXor(1, 5)->andBitXor(2, 7)->toArray());
    }

    public function testSplice() : void
    {
        self::assertSame([[':', 1, 0, 5, 'foo'], [':', 2, 1, 7, 'bar']], Operations::splice(1, 0, 5, 'foo')->andSplice(2, 1, 7, 'bar')->toArray());
    }

    public function testInsert() : void
    {
        self::assertSame([['!', 1, 5], ['!', 2, 7]], Operations::insert(1, 5)->andInsert(2, 7)->toArray());
    }

    public function testDelete() : void
    {
        self::assertSame([['#', 1, 5], ['#', 2, 7]], Operations::delete(1, 5)->andDelete(2, 7)->toArray());
    }

    public function testSet() : void
    {
        self::assertSame([['=', 1, 5], ['=', 2, 7]], Operations::set(1, 5)->andSet(2, 7)->toArray());
    }
}

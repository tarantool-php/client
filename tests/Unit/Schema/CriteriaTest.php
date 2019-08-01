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
use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\IteratorTypes;

final class CriteriaTest extends TestCase
{
    public function testIndexId() : void
    {
        self::assertSame(1, Criteria::index(1)->getIndex());
    }

    public function testAndIndexId() : void
    {
        self::assertSame(2, Criteria::index(1)->andIndex(2)->getIndex());
    }

    public function testIndexName() : void
    {
        self::assertSame('foo', Criteria::index('foo')->getIndex());
    }

    public function testAndIndexName() : void
    {
        self::assertSame('bar', Criteria::index('foo')->andIndex('bar')->getIndex());
    }

    public function testKey() : void
    {
        self::assertSame([1], Criteria::key([1])->getKey());
    }

    public function testAndKey() : void
    {
        self::assertSame([2], Criteria::key([1])->andKey([2])->getKey());
    }

    public function testLimit() : void
    {
        self::assertSame(1, Criteria::limit(1)->getLimit());
    }

    public function testAndLimit() : void
    {
        self::assertSame(2, Criteria::limit(1)->andLimit(2)->getLimit());
    }

    public function testOffset() : void
    {
        self::assertSame(1, Criteria::offset(1)->getOffset());
    }

    public function testAndOffset() : void
    {
        self::assertSame(2, Criteria::offset(1)->andOffset(2)->getOffset());
    }

    public function testIteratorType() : void
    {
        self::assertSame(IteratorTypes::ALL, Criteria::iteratorType(IteratorTypes::ALL)->getIteratorType());
    }

    public function testAndIteratorType() : void
    {
        self::assertSame(IteratorTypes::GE, Criteria::iteratorType(IteratorTypes::ALL)->andIteratorType(IteratorTypes::GE)->getIteratorType());
    }

    /**
     * @dataProvider provideIteratorTypeNames
     */
    public function testIteratorTypeByName(string $name) : void
    {
        $method = str_replace('_', '', $name).'iterator';

        self::assertSame(constant(IteratorTypes::class.'::'.$name), [Criteria::class, $method]()->getIteratorType());
    }

    /**
     * @dataProvider provideIteratorTypeNames
     */
    public function testAndIteratorTypeByName(string $name) : void
    {
        // make sure we don't assign the same iterator twice
        $method = 'EQ' === $name ? 'allIterator' : 'eqIterator';
        $andMethod = 'and'.str_replace('_', '', $name).'iterator';

        self::assertSame(constant(IteratorTypes::class.'::'.$name), [Criteria::class, $method]()->$andMethod()->getIteratorType());
    }

    public function provideIteratorTypeNames() : iterable
    {
        return [
            ['EQ'],
            ['REQ'],
            ['ALL'],
            ['LT'],
            ['LE'],
            ['GE'],
            ['GT'],
            ['BITS_ALL_SET'],
            ['BITS_ANY_SET'],
            ['BITS_ALL_NOT_SET'],
            ['OVERLAPS'],
            ['NEIGHBOR'],
        ];
    }

    public function testDefaultIteratorTypeIsChosenAutomaticallyBasedOnKeyValue() : void
    {
        self::assertSame(IteratorTypes::ALL, Criteria::index(1)->getIteratorType());
        self::assertSame(IteratorTypes::ALL, Criteria::key([])->getIteratorType());
        self::assertSame(IteratorTypes::EQ, Criteria::key([3])->getIteratorType());

        $criteria = Criteria::key([]);
        $criteria->getIteratorType();
        $criteria->getKey();

        self::assertSame(IteratorTypes::EQ, $criteria->andKey([3])->getIteratorType());

        $criteria = Criteria::key([3]);
        $criteria->getIteratorType();
        $criteria->getKey();

        self::assertSame(IteratorTypes::ALL, $criteria->andKey([])->getIteratorType());
    }
}

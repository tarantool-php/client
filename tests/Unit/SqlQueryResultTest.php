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

namespace Tarantool\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\SqlQueryResult;

final class SqlQueryResultTest extends TestCase
{
    private const DATA = [
        [1, 'foo'],
        [2, 'bar'],
    ];

    private const METADATA = [
        ['column1', 'integer'],
        ['column2', 'string'],
    ];

    public function testGetData() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame(self::DATA, $result->getData());
        self::assertSame(self::METADATA, $result->getMetadata());
    }

    public function testGetMetadata() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame(self::METADATA, $result->getMetadata());
    }

    public function testIsEmptyReturnsTrue() : void
    {
        $result = new SqlQueryResult([], []);

        self::assertTrue($result->isEmpty());
    }

    public function testIsEmptyReturnsFalse() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertFalse($result->isEmpty());
    }

    public function testGetFirst() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame([
            self::METADATA[0][0] => self::DATA[0][0],
            self::METADATA[1][0] => self::DATA[0][1],
        ], $result->getFirst());
    }

    public function testGetFirstReturnsNull() : void
    {
        $result = new SqlQueryResult([], []);

        self::assertNull($result->getFirst());
    }

    public function testGetLast() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame([
            self::METADATA[0][0] => self::DATA[1][0],
            self::METADATA[1][0] => self::DATA[1][1],
        ], $result->getLast());
    }

    public function testGetLastReturnsNull() : void
    {
        $result = new SqlQueryResult([], []);

        self::assertNull($result->getLast());
    }

    public function testIterable() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertIsIterable($result);

        $count = 0;
        foreach ($result as $item) {
            self::assertSame([
                self::METADATA[0][0] => self::DATA[$count][0],
                self::METADATA[1][0] => self::DATA[$count][1],
            ], $item);
            ++$count;
        }

        self::assertSame(2, $count);
    }

    public function testCountable() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertCount(2, $result);
    }

    public function testOffsetGetReturnsFirstItem() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame($result->getFirst(), $result[0]);
    }

    public function testOffsetGetReturnsSecondItem() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame($result->getLast(), $result[1]);
    }

    public function testOffsetGetFailsOnInvalidOffset() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('The offset "2" does not exist');
        $result[2];
    }

    public function testOffsetIssetReturnsTrue() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertTrue(isset($result[0]));
    }

    public function testOffsetIssetReturnsFalse() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertFalse(isset($result[2]));
    }

    public function testOffsetSetIsForbidden() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        $this->expectException(\BadMethodCallException::class);
        $result[2] = [
            self::METADATA[0][0] => self::DATA[0][0],
            self::METADATA[1][0] => self::DATA[0][1],
        ];
    }

    public function testOffsetUnsetIsForbidden() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        $this->expectException(\BadMethodCallException::class);
        unset($result[0]);
    }
}

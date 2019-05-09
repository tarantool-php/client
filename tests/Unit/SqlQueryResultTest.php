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
        ['COLUMN1', 'INTEGER'],
        ['COLUMN2', 'TEXT'],
    ];

    public function testGetters() : void
    {
        $result = new SqlQueryResult(self::DATA, self::METADATA);

        self::assertSame(self::DATA, $result->getData());
        self::assertSame(self::METADATA, $result->getMetadata());
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
}

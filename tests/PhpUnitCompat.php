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

namespace Tarantool\Client\Tests;

/**
 * Compatibility layer for legacy PHPUnit versions.
 */
trait PhpUnitCompat
{
    /**
     * TestCase::expectExceptionMessageRegExp() is deprecated since PHPUnit 8.4.
     */
    public function expectExceptionMessageMatches(string $regularExpression) : void
    {
        is_callable(parent::class.'::expectExceptionMessageMatches')
            ? parent::expectExceptionMessageMatches(...func_get_args())
            : parent::expectExceptionMessageRegExp(...func_get_args());
    }

    /**
     * Assert::assertEquals()'s $delta, $maxDepth, $canonicalize parameters are deprecated since PHPUnit 8.0.
     */
    public static function assertEqualsCanonicalizing($expected, $actual, string $message = '') : void
    {
        is_callable(parent::class.'::assertEqualsCanonicalizing')
            ? parent::assertEqualsCanonicalizing($expected, $actual, $message)
            : parent::assertEquals($expected, $actual, $message, 0.0, 10, true);
    }

    /**
     * Assert::assertRegExp() is deprecated since PHPUnit 9.1.
     */
    public static function assertMatchesRegularExpression(string $pattern, string $string, string $message = '') : void
    {
        is_callable(parent::class.'::assertMatchesRegularExpression')
            ? parent::assertMatchesRegularExpression(...func_get_args())
            : parent::assertRegExp(...func_get_args());
    }
}

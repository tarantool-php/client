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

namespace Tarantool\Client\Schema;

final class IteratorTypes
{
    public const EQ = 0;
    public const REQ = 1;
    public const ALL = 2;
    public const LT = 3;
    public const LE = 4;
    public const GE = 5;
    public const GT = 6;
    public const BITS_ALL_SET = 7;
    public const BITS_ANY_SET = 8;
    public const BITS_ALL_NOT_SET = 9;
    public const OVERLAPS = 10;
    public const NEIGHBOR = 11;

    private function __construct()
    {
    }
}

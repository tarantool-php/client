<?php

namespace Tarantool\Client\Schema;

abstract class Iterator
{
    const EQ = 0;
    const REQ = 1;
    const ALL = 2;
    const LT = 3;
    const LE = 4;
    const GE = 5;
    const GT = 6;
    const BITS_ALL_SET = 7;
    const BITS_ANY_SET = 8;
    const BITS_ALL_NOT_SET = 9;
    const OVERLAPS = 10;
    const NEIGHBOR = 11;
}

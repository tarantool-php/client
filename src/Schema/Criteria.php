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

namespace Tarantool\Client\Schema;

final class Criteria
{
    private $index = 0;
    private $key = [];
    private $limit = \PHP_INT_MAX & 0xffffffff;
    private $offset = 0;
    private $iteratorType;

    private function __construct()
    {
    }

    /**
     * @param int|string $index
     */
    public static function index($index) : self
    {
        $self = new self();
        $self->index = $index;

        return $self;
    }

    /**
     * @param int|string $index
     */
    public function andIndex($index) : self
    {
        $new = clone $this;
        $new->index = $index;

        return $new;
    }

    /**
     * @return int|string
     */
    public function getIndex()
    {
        return $this->index;
    }

    public static function key(array $key) : self
    {
        $self = new self();
        $self->key = $key;

        return $self;
    }

    public function andKey(array $key) : self
    {
        $new = clone $this;
        $new->key = $key;

        return $new;
    }

    public function getKey() : array
    {
        return $this->key;
    }

    public static function limit(int $limit) : self
    {
        $self = new self();
        $self->limit = $limit;

        return $self;
    }

    public function andLimit(int $limit) : self
    {
        $new = clone $this;
        $new->limit = $limit;

        return $new;
    }

    public function getLimit() : int
    {
        return $this->limit;
    }

    public static function offset(int $offset) : self
    {
        $self = new self();
        $self->offset = $offset;

        return $self;
    }

    public function andOffset(int $offset) : self
    {
        $new = clone $this;
        $new->offset = $offset;

        return $new;
    }

    public function getOffset() : int
    {
        return $this->offset;
    }

    public static function iteratorType(int $iteratorType) : self
    {
        $self = new self();
        $self->iteratorType = $iteratorType;

        return $self;
    }

    public function andIteratorType(int $iteratorType) : self
    {
        $new = clone $this;
        $new->iteratorType = $iteratorType;

        return $new;
    }

    public static function eqIterator() : self
    {
        return self::iteratorType(IteratorTypes::EQ);
    }

    public function andEqIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::EQ);
    }

    public static function reqIterator() : self
    {
        return self::iteratorType(IteratorTypes::REQ);
    }

    public function andReqIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::REQ);
    }

    public static function allIterator() : self
    {
        return self::iteratorType(IteratorTypes::ALL);
    }

    public function andAllIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::ALL);
    }

    public static function ltIterator() : self
    {
        return self::iteratorType(IteratorTypes::LT);
    }

    public function andLtIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::LT);
    }

    public static function leIterator() : self
    {
        return self::iteratorType(IteratorTypes::LE);
    }

    public function andLeIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::LE);
    }

    public static function geIterator() : self
    {
        return self::iteratorType(IteratorTypes::GE);
    }

    public function andGeIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::GE);
    }

    public static function gtIterator() : self
    {
        return self::iteratorType(IteratorTypes::GT);
    }

    public function andGtIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::GT);
    }

    public static function bitsAllSetIterator() : self
    {
        return self::iteratorType(IteratorTypes::BITS_ALL_SET);
    }

    public function andBitsAllSetIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::BITS_ALL_SET);
    }

    public static function bitsAnySetIterator() : self
    {
        return self::iteratorType(IteratorTypes::BITS_ANY_SET);
    }

    public function andBitsAnySetIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::BITS_ANY_SET);
    }

    public static function bitsAllNotSetIterator() : self
    {
        return self::iteratorType(IteratorTypes::BITS_ALL_NOT_SET);
    }

    public function andBitsAllNotSetIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::BITS_ALL_NOT_SET);
    }

    public static function overlapsIterator() : self
    {
        return self::iteratorType(IteratorTypes::OVERLAPS);
    }

    public function andOverlapsIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::OVERLAPS);
    }

    public static function neighborIterator() : self
    {
        return self::iteratorType(IteratorTypes::NEIGHBOR);
    }

    public function andNeighborIterator() : self
    {
        return $this->andIteratorType(IteratorTypes::NEIGHBOR);
    }

    public function getIteratorType() : int
    {
        if (null !== $this->iteratorType) {
            return $this->iteratorType;
        }

        return [] === $this->key ? IteratorTypes::ALL : IteratorTypes::EQ;
    }
}

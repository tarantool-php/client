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

namespace Tarantool\Client\Schema;

final class Criteria
{
    private $index = 0;
    private $key = [];
    private $limit = \PHP_INT_MAX & 0xffffffff;
    private $offset = 0;
    private $iteratorType = IteratorTypes::EQ;

    private function __construct()
    {
    }

    public static function index($index) : self
    {
        $self = new self();
        $self->index = $index;

        return $self;
    }

    public function andIndex($index) : self
    {
        $new = clone $this;
        $new->index = $index;

        return $new;
    }

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

    public static function eqIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::EQ;

        return $self;
    }

    public function andEqIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::EQ;

        return $new;
    }

    public static function reqIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::REQ;

        return $self;
    }

    public function andReqIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::REQ;

        return $new;
    }

    public static function allIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::ALL;

        return $self;
    }

    public function andAllIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::ALL;

        return $new;
    }

    public static function ltIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::LT;

        return $self;
    }

    public function andLtIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::LT;

        return $new;
    }

    public static function leIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::LE;

        return $self;
    }

    public function andLeIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::LE;

        return $new;
    }

    public static function geIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::GE;

        return $self;
    }

    public function andGeIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::GE;

        return $new;
    }

    public static function gtIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::GT;

        return $self;
    }

    public function andGtIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::GT;

        return $new;
    }

    public static function bitsAllSetIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::BITS_ALL_SET;

        return $self;
    }

    public function andBitsAllSetIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::BITS_ALL_SET;

        return $new;
    }

    public static function bitsAnySetIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::BITS_ANY_SET;

        return $self;
    }

    public function andBitsAnySetIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::BITS_ANY_SET;

        return $new;
    }

    public static function bitsAllNotSetIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::BITS_ALL_NOT_SET;

        return $self;
    }

    public function andBitsAllNotSetIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::BITS_ALL_NOT_SET;

        return $new;
    }

    public static function overlapsIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::OVERLAPS;

        return $self;
    }

    public function andOverlapsIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::OVERLAPS;

        return $new;
    }

    public static function neighborIterator() : self
    {
        $self = new self();
        $self->iteratorType = IteratorTypes::NEIGHBOR;

        return $self;
    }

    public function andNeighborIterator() : self
    {
        $new = clone $this;
        $new->iteratorType = IteratorTypes::NEIGHBOR;

        return $new;
    }

    public function getIteratorType() : int
    {
        return $this->iteratorType;
    }
}

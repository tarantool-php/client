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

final class Operations
{
    private $operations;

    private function __construct(array $operation)
    {
        $this->operations = [$operation];
    }

    public static function add(int $fieldNumber, int $value) : self
    {
        return new self(['+', $fieldNumber, $value]);
    }

    public function andAdd(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['+', $fieldNumber, $value];

        return $self;
    }

    public static function subtract(int $fieldNumber, int $value) : self
    {
        return new self(['-', $fieldNumber, $value]);
    }

    public function andSubtract(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['-', $fieldNumber, $value];

        return $self;
    }

    public static function bitAnd(int $fieldNumber, int $value) : self
    {
        return new self(['&', $fieldNumber, $value]);
    }

    public function andBitAnd(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['&', $fieldNumber, $value];

        return $self;
    }

    public static function bitOr(int $fieldNumber, int $value) : self
    {
        return new self(['|', $fieldNumber, $value]);
    }

    public function andBitOr(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['|', $fieldNumber, $value];

        return $self;
    }

    public static function bitXor(int $fieldNumber, int $value) : self
    {
        return new self(['^', $fieldNumber, $value]);
    }

    public function andBitXor(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['^', $fieldNumber, $value];

        return $self;
    }

    public static function splice(int $fieldNumber, int $offset, int $length, string $replacement) : self
    {
        return new self([':', $fieldNumber, $offset, $length, $replacement]);
    }

    public function andSplice(int $fieldNumber, int $offset, int $length, string $replacement) : self
    {
        $self = clone $this;
        $self->operations[] = [':', $fieldNumber, $offset, $length, $replacement];

        return $self;
    }

    public static function insert(int $fieldNumber, int $value) : self
    {
        return new self(['!', $fieldNumber, $value]);
    }

    public function andInsert(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['!', $fieldNumber, $value];

        return $self;
    }

    public static function delete(int $fieldNumber, int $value) : self
    {
        return new self(['#', $fieldNumber, $value]);
    }

    public function andDelete(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['#', $fieldNumber, $value];

        return $self;
    }

    public static function set(int $fieldNumber, $value) : self
    {
        return new self(['=', $fieldNumber, $value]);
    }

    public function andSet(int $fieldNumber, $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['=', $fieldNumber, $value];

        return $self;
    }

    public function toArray() : array
    {
        return $this->operations;
    }
}

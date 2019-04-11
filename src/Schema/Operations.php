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

    public function withAdd(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['+', $fieldNumber, $value];

        return $self;
    }

    public static function subtract(int $fieldNumber, int $value) : self
    {
        return new self(['-', $fieldNumber, $value]);
    }

    public function withSubtract(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['-', $fieldNumber, $value];

        return $self;
    }

    public static function bitAnd(int $fieldNumber, int $value) : self
    {
        return new self(['&', $fieldNumber, $value]);
    }

    public function withBitAnd(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['&', $fieldNumber, $value];

        return $self;
    }

    public static function bitOr(int $fieldNumber, int $value) : self
    {
        return new self(['|', $fieldNumber, $value]);
    }

    public function withBitOr(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['|', $fieldNumber, $value];

        return $self;
    }

    public static function bitXor(int $fieldNumber, int $value) : self
    {
        return new self(['^', $fieldNumber, $value]);
    }

    public function withBitXor(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['^', $fieldNumber, $value];

        return $self;
    }

    public static function splice(int $fieldNumber, int $offset, int $length, string $replacement) : self
    {
        return new self([':', $fieldNumber, $offset, $length, $replacement]);
    }

    public function withSplice(int $fieldNumber, int $offset, int $length, string $replacement) : self
    {
        $self = clone $this;
        $self->operations[] = [':', $fieldNumber, $offset, $length, $replacement];

        return $self;
    }

    public static function insert(int $fieldNumber, int $value) : self
    {
        return new self(['!', $fieldNumber, $value]);
    }

    public function withInsert(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['!', $fieldNumber, $value];

        return $self;
    }

    public static function delete(int $fieldNumber, int $value) : self
    {
        return new self(['#', $fieldNumber, $value]);
    }

    public function withDelete(int $fieldNumber, int $value) : self
    {
        $self = clone $this;
        $self->operations[] = ['#', $fieldNumber, $value];

        return $self;
    }

    public static function set(int $fieldNumber, $value) : self
    {
        return new self(['=', $fieldNumber, $value]);
    }

    public function withSet(int $fieldNumber, $value) : self
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

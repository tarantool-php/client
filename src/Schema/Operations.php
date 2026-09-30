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

final class Operations
{
    /** @var non-empty-array<int, array> */
    private array $operations;

    /**
     * @param non-empty-array<int, mixed> $operation
     */
    private function __construct(array $operation)
    {
        $this->operations = [$operation];
    }

    public static function add(int|string $field, int $value) : self
    {
        return new self(['+', $field, $value]);
    }

    public function andAdd(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['+', $field, $value];

        return $new;
    }

    public static function subtract(int|string $field, int $value) : self
    {
        return new self(['-', $field, $value]);
    }

    public function andSubtract(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['-', $field, $value];

        return $new;
    }

    public static function bitwiseAnd(int|string $field, int $value) : self
    {
        return new self(['&', $field, $value]);
    }

    public function andBitwiseAnd(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['&', $field, $value];

        return $new;
    }

    public static function bitwiseOr(int|string $field, int $value) : self
    {
        return new self(['|', $field, $value]);
    }

    public function andBitwiseOr(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['|', $field, $value];

        return $new;
    }

    public static function bitwiseXor(int|string $field, int $value) : self
    {
        return new self(['^', $field, $value]);
    }

    public function andBitwiseXor(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['^', $field, $value];

        return $new;
    }

    public static function splice(int|string $field, int $offset, int $length, string $replacement) : self
    {
        return new self([':', $field, $offset, $length, $replacement]);
    }

    public function andSplice(int|string $field, int $offset, int $length, string $replacement) : self
    {
        $new = clone $this;
        $new->operations[] = [':', $field, $offset, $length, $replacement];

        return $new;
    }

    public static function insert(int|string $field, int $value) : self
    {
        return new self(['!', $field, $value]);
    }

    public function andInsert(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['!', $field, $value];

        return $new;
    }

    public static function delete(int|string $field, int $value) : self
    {
        return new self(['#', $field, $value]);
    }

    public function andDelete(int|string $field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['#', $field, $value];

        return $new;
    }

    public static function set(int|string $field, $value) : self
    {
        return new self(['=', $field, $value]);
    }

    public function andSet(int|string $field, $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['=', $field, $value];

        return $new;
    }

    public function toArray() : array
    {
        return $this->operations;
    }
}

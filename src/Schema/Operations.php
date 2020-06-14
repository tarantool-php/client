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
    private $operations;

    /**
     * @param non-empty-array<int, mixed> $operation
     */
    private function __construct($operation)
    {
        $this->operations = [$operation];
    }

    /**
     * @param int|string $field
     */
    public static function add($field, int $value) : self
    {
        return new self(['+', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andAdd($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['+', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function subtract($field, int $value) : self
    {
        return new self(['-', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andSubtract($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['-', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function bitwiseAnd($field, int $value) : self
    {
        return new self(['&', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andBitwiseAnd($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['&', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function bitwiseOr($field, int $value) : self
    {
        return new self(['|', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andBitwiseOr($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['|', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function bitwiseXor($field, int $value) : self
    {
        return new self(['^', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andBitwiseXor($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['^', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function splice($field, int $offset, int $length, string $replacement) : self
    {
        return new self([':', $field, $offset, $length, $replacement]);
    }

    /**
     * @param int|string $field
     */
    public function andSplice($field, int $offset, int $length, string $replacement) : self
    {
        $new = clone $this;
        $new->operations[] = [':', $field, $offset, $length, $replacement];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function insert($field, int $value) : self
    {
        return new self(['!', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andInsert($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['!', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function delete($field, int $value) : self
    {
        return new self(['#', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andDelete($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['#', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     * @param mixed $value
     */
    public static function set($field, $value) : self
    {
        return new self(['=', $field, $value]);
    }

    /**
     * @param int|string $field
     * @param mixed $value
     */
    public function andSet($field, $value) : self
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

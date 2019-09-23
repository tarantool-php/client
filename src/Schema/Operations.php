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

final class Operations
{
    private $operations;

    private function __construct(array $operation)
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
    public static function bitAnd($field, int $value) : self
    {
        return new self(['&', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andBitAnd($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['&', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function bitOr($field, int $value) : self
    {
        return new self(['|', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andBitOr($field, int $value) : self
    {
        $new = clone $this;
        $new->operations[] = ['|', $field, $value];

        return $new;
    }

    /**
     * @param int|string $field
     */
    public static function bitXor($field, int $value) : self
    {
        return new self(['^', $field, $value]);
    }

    /**
     * @param int|string $field
     */
    public function andBitXor($field, int $value) : self
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
     */
    public static function set($field, $value) : self
    {
        return new self(['=', $field, $value]);
    }

    /**
     * @param int|string $field
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

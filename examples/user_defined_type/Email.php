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

final class Email
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toString() : string
    {
        return $this->value;
    }
}

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

namespace App;

final class Email
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function equals(self $email) : bool
    {
        return $this->value === $email->value;
    }

    public function toString() : string
    {
        return $this->value;
    }
}

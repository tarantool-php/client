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

namespace App;

final class Email
{
    public function __construct(private readonly string $value)
    {
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

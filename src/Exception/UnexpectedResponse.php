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

namespace Tarantool\Client\Exception;

final class UnexpectedResponse extends \RuntimeException
{
    public static function outOfSync(int $expectedSync, int $actualSync) : self
    {
        return new self("Unexpected response received: expected sync #$expectedSync, got #$actualSync");
    }
}

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

final class UnexpectedResponse extends \RuntimeException implements ClientException
{
    public static function fromSync(int $expected, int $actual) : self
    {
        return new self("Unexpected response received: expected sync #$expected, got #$actual.");
    }
}

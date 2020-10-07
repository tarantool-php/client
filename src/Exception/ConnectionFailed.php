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

namespace Tarantool\Client\Exception;

final class ConnectionFailed extends \RuntimeException implements ClientException
{
    public static function fromUriAndReason(string $uri, string $reason) : self
    {
        throw new self(\sprintf('Failed to connect to %s: %s', $uri, $reason));
    }
}

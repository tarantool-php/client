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

final class CommunicationFailed extends \RuntimeException implements ClientException
{
    public static function withLastPhpError(string $errorMessage) : self
    {
        $error = \error_get_last();

        return new self($error ? \sprintf('%s: %s', $errorMessage, $error['message']) : $errorMessage);
    }
}

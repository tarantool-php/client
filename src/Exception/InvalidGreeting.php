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

namespace Tarantool\Client\Exception;

final class InvalidGreeting extends \RuntimeException implements ClientException
{
    public static function invalidServerName() : self
    {
        return new self('Unable to recognize Tarantool server.');
    }

    public static function invalidSalt() : self
    {
        return new self('Unable to parse salt.');
    }
}

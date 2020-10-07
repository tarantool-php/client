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

use Tarantool\Client\Request\Request;

final class RequestDenied extends \RuntimeException implements ClientException
{
    public static function fromObject(Request $request) : self
    {
        return new self(\sprintf('Request "%s" is denied', \get_class($request)));
    }
}

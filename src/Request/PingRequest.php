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

namespace Tarantool\Client\Request;

use Tarantool\Client\RequestTypes;

final class PingRequest implements Request
{
    public function getType() : int
    {
        return RequestTypes::PING;
    }

    public function getBody() : array
    {
        return [];
    }
}

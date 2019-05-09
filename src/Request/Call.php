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

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;
use Tarantool\Client\RequestTypes;

final class Call implements Request
{
    private $funcName;
    private $args;

    public function __construct(string $funcName, array $args = [])
    {
        $this->funcName = $funcName;
        $this->args = $args;
    }

    public function getType() : int
    {
        return RequestTypes::CALL;
    }

    public function getBody() : array
    {
        return [
            IProto::FUNCTION_NAME => $this->funcName,
            IProto::TUPLE => $this->args,
        ];
    }
}

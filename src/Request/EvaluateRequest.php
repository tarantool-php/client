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

namespace Tarantool\Client\Request;

use Tarantool\Client\Keys;
use Tarantool\Client\RequestTypes;

final class EvaluateRequest implements Request
{
    /** @var non-empty-array<int, string|array> */
    private $body;

    public function __construct(string $expr, array $args = [])
    {
        $this->body = [
            Keys::EXPR => $expr,
            Keys::TUPLE => $args,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::EVALUATE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

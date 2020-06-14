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

final class CallRequest implements Request
{
    /** @var non-empty-array<int, string|array> */
    private $body;

    public function __construct(string $funcName, array $args = [])
    {
        $this->body = [
            Keys::FUNCTION_NAME => $funcName,
            Keys::TUPLE => $args,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::CALL;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

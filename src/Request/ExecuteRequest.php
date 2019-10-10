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

use Tarantool\Client\Keys;
use Tarantool\Client\RequestTypes;

final class ExecuteRequest implements Request
{
    private $body;

    public function __construct(string $sql, array $params = [])
    {
        $this->body = [] === $params ? [
            Keys::SQL_TEXT => $sql,
        ] : [
            Keys::SQL_TEXT => $sql,
            Keys::SQL_BIND => $params,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::EXECUTE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

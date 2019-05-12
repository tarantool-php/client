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

final class ExecuteRequest implements Request
{
    private $sql;
    private $params;

    public function __construct(string $sql, array $params = [])
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function getType() : int
    {
        return RequestTypes::EXECUTE;
    }

    public function getBody() : array
    {
        return [] === $this->params ? [
            IProto::SQL_TEXT => $this->sql,
        ] : [
            IProto::SQL_TEXT => $this->sql,
            IProto::SQL_BIND => $this->params,
        ];
    }
}

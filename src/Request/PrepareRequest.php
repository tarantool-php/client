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

final class PrepareRequest implements Request
{
    /** @var non-empty-array<int, int|string> */
    private $body;

    /**
     * @param non-empty-array<int, int|string> $body
     */
    private function __construct($body)
    {
        $this->body = $body;
    }

    public static function fromSql(string $sql) : self
    {
        return new self([Keys::SQL_TEXT => $sql]);
    }

    public static function fromStatementId(int $statementId) : self
    {
        return new self([Keys::STMT_ID => $statementId]);
    }

    public function getType() : int
    {
        return RequestTypes::PREPARE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

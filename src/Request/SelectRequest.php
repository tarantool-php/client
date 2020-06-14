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

final class SelectRequest implements Request
{
    /** @var non-empty-array<int, int|array> */
    private $body;

    public function __construct(int $spaceId, int $indexId, array $key, int $offset, int $limit, int $iteratorType)
    {
        $this->body = [
            Keys::KEY => $key,
            Keys::SPACE_ID => $spaceId,
            Keys::INDEX_ID => $indexId,
            Keys::LIMIT => $limit,
            Keys::OFFSET => $offset,
            Keys::ITERATOR => $iteratorType,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::SELECT;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

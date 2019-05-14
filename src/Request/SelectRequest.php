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

final class SelectRequest implements Request
{
    private $spaceId;
    private $indexId;
    private $key;
    private $offset;
    private $limit;
    private $iteratorType;

    public function __construct(int $spaceId, int $indexId, array $key, int $offset, int $limit, int $iteratorType)
    {
        $this->spaceId = $spaceId;
        $this->indexId = $indexId;
        $this->key = $key;
        $this->offset = $offset;
        $this->limit = $limit;
        $this->iteratorType = $iteratorType;
    }

    public function getType() : int
    {
        return RequestTypes::SELECT;
    }

    public function getBody() : array
    {
        return [
            Keys::KEY => $this->key,
            Keys::SPACE_ID => $this->spaceId,
            Keys::INDEX_ID => $this->indexId,
            Keys::LIMIT => $this->limit,
            Keys::OFFSET => $this->offset,
            Keys::ITERATOR => $this->iteratorType,
        ];
    }
}

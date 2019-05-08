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

use Tarantool\Client\IProto;
use Tarantool\Client\RequestTypes;

final class Select implements Request
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
            IProto::KEY => $this->key,
            IProto::SPACE_ID => $this->spaceId,
            IProto::INDEX_ID => $this->indexId,
            IProto::LIMIT => $this->limit,
            IProto::OFFSET => $this->offset,
            IProto::ITERATOR => $this->iteratorType,
        ];
    }
}

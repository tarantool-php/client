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

final class Delete implements Request
{
    private $spaceId;
    private $indexId;
    private $key;

    public function __construct(int $spaceId, int $indexId, array $key)
    {
        $this->spaceId = $spaceId;
        $this->indexId = $indexId;
        $this->key = $key;
    }

    public function getType() : int
    {
        return RequestTypes::DELETE;
    }

    public function getBody() : array
    {
        return [
            IProto::SPACE_ID => $this->spaceId,
            IProto::INDEX_ID => $this->indexId,
            IProto::KEY => $this->key,
        ];
    }
}

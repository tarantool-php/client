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

final class UpdateRequest implements Request
{
    private $spaceId;
    private $indexId;
    private $key;
    private $operations;

    public function __construct(int $spaceId, int $indexId, array $key, array $operations)
    {
        $this->spaceId = $spaceId;
        $this->indexId = $indexId;
        $this->key = $key;
        $this->operations = $operations;
    }

    public function getType() : int
    {
        return RequestTypes::UPDATE;
    }

    public function getBody() : array
    {
        return [
            Keys::SPACE_ID => $this->spaceId,
            Keys::INDEX_ID => $this->indexId,
            Keys::KEY => $this->key,
            Keys::TUPLE => $this->operations,
        ];
    }
}

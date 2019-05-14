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

final class UpsertRequest implements Request
{
    private $spaceId;
    private $tuple;
    private $operations;

    public function __construct(int $spaceId, array $tuple, array $operations)
    {
        $this->spaceId = $spaceId;
        $this->tuple = $tuple;
        $this->operations = $operations;
    }

    public function getType() : int
    {
        return RequestTypes::UPSERT;
    }

    public function getBody() : array
    {
        return [
            Keys::SPACE_ID => $this->spaceId,
            Keys::TUPLE => $this->tuple,
            Keys::OPERATIONS => $this->operations,
        ];
    }
}

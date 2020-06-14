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

final class UpsertRequest implements Request
{
    /** @var non-empty-array<int, int|array> */
    private $body;

    public function __construct(int $spaceId, array $tuple, array $operations)
    {
        $this->body = [
            Keys::SPACE_ID => $spaceId,
            Keys::TUPLE => $tuple,
            Keys::OPERATIONS => $operations,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::UPSERT;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

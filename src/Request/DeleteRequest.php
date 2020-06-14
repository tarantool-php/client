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

final class DeleteRequest implements Request
{
    /** @var non-empty-array<int, int|array> */
    private $body;

    public function __construct(int $spaceId, int $indexId, array $key)
    {
        $this->body = [
            Keys::SPACE_ID => $spaceId,
            Keys::INDEX_ID => $indexId,
            Keys::KEY => $key,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::DELETE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

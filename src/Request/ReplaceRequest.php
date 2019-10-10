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

final class ReplaceRequest implements Request
{
    private $body;

    public function __construct(int $spaceId, array $tuple)
    {
        $this->body = [
            Keys::SPACE_ID => $spaceId,
            Keys::TUPLE => $tuple,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::REPLACE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

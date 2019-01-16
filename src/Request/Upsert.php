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

final class Upsert implements Request
{
    private $spaceId;
    private $values;
    private $operations;

    public function __construct(int $spaceId, array $values, array $operations)
    {
        $this->spaceId = $spaceId;
        $this->values = $values;
        $this->operations = $operations;
    }

    public function getType() : int
    {
        return RequestTypes::UPSERT;
    }

    public function getBody() : array
    {
        return [
            IProto::SPACE_ID => $this->spaceId,
            IProto::TUPLE => $this->values,
            IProto::OPERATIONS => $this->operations,
        ];
    }
}

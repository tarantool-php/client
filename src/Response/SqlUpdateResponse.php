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

namespace Tarantool\Client\Response;

use Tarantool\Client\IProto;

final class SqlUpdateResponse
{
    private $sync;
    private $sqlInfo;

    public function __construct(int $sync, array $sqlInfo)
    {
        $this->sync = $sync;
        $this->sqlInfo = $sqlInfo;
    }

    public function getSync() : int
    {
        return $this->sync;
    }

    public function getRowCount() : int
    {
        return $this->sqlInfo[0];
    }

    public static function createFromRaw(RawResponse $response) : self
    {
        return new self(
            $response->getHeaderField(IProto::SYNC),
            $response->getBodyField(IProto::SQL_INFO)
        );
    }
}

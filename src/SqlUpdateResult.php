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

namespace Tarantool\Client;

final class SqlUpdateResult implements \Countable
{
    private $info;

    public function __construct(array $info)
    {
        $this->info = $info;
    }

    public function count() : int
    {
        return $this->info[Keys::SQL_INFO_ROW_COUNT];
    }

    public function getAutoincrementIds() : array
    {
        return $this->info[Keys::SQL_INFO_AUTO_INCREMENT_IDS] ?? [];
    }
}

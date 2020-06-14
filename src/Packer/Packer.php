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

namespace Tarantool\Client\Packer;

use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

interface Packer
{
    public function pack(Request $request, int $sync) : string;

    public function unpack(string $packet) : Response;
}

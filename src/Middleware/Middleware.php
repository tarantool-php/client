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

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

interface Middleware
{
    public function process(Request $request, Handler $handler) : Response;
}

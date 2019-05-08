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

namespace Tarantool\Client\Handler;

use Tarantool\Client\Connection\Connection;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

interface Handler
{
    /**
     * @param Request $request
     *
     * @throws RequestFailed
     *
     * @return Response
     */
    public function handle(Request $request) : Response;

    public function getConnection() : Connection;

    public function getPacker() : Packer;
}

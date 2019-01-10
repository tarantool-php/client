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

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\ConnectionException;
use Tarantool\Client\Exception\Exception;

interface Connection
{
    /**
     * Opens a new connection.
     *
     * @throws ConnectionException|Exception
     *
     * @return string A session salt
     */
    public function open() : string;

    /**
     * Closes an opened connection.
     */
    public function close() : void;

    /**
     * Indicates whether a connection is closed.
     *
     * @return bool
     */
    public function isClosed() : bool;

    /**
     * Sends a msgpack request and gets a msgpack response back.
     *
     * @param string $data
     *
     * @return string
     */
    public function send(string $data) : string;
}

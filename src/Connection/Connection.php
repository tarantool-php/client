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

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;

interface Connection
{
    /**
     * Opens a new connection.
     *
     * @throws ConnectionFailed|CommunicationFailed
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
     * Sends a MessagePack request and gets a MessagePack response back.
     *
     * @param string $data
     *
     * @throws CommunicationFailed
     *
     * @return string
     */
    public function send(string $data) : string;
}

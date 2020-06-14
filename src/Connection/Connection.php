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

namespace Tarantool\Client\Connection;

use Tarantool\Client\Exception\CommunicationFailed;
use Tarantool\Client\Exception\ConnectionFailed;

interface Connection
{
    /**
     * Opens a new connection or reuses an existing one.
     *
     * @throws ConnectionFailed
     * @throws CommunicationFailed
     */
    public function open() : Greeting;

    /**
     * Closes an opened connection.
     */
    public function close() : void;

    /**
     * Indicates whether a connection is closed.
     */
    public function isClosed() : bool;

    /**
     * Sends a MessagePack request and gets a MessagePack response back.
     *
     * @throws CommunicationFailed
     */
    public function send(string $data) : string;
}

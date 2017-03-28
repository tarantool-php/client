<?php

namespace Tarantool\Client\Connection;

interface Connection
{
    /**
     * Opens a new connection.
     *
     * @return string A session salt
     */
    public function open();

    /**
     * Closes an opened connection.
     */
    public function close();

    /**
     * Indicates whether a connection is closed.
     *
     * @return bool
     */
    public function isClosed();

    /**
     * Sends a msgpack request and gets a msgpack response back.
     *
     * @param string $data
     *
     * @return string
     */
    public function send($data);
}

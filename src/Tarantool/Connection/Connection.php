<?php

namespace Tarantool\Connection;

interface Connection
{
    public function connect();
    public function disconnect();
    public function isConnected();
    public function send($data);
}

<?php

namespace Tarantool\Connection;

interface Connection
{
    public function open();
    public function close();
    public function isClosed();
    public function send($data);
}

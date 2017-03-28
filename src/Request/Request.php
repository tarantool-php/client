<?php

namespace Tarantool\Client\Request;

interface Request
{
    const TYPE_OK = 0;
    const TYPE_SELECT = 1;
    const TYPE_INSERT = 2;
    const TYPE_REPLACE = 3;
    const TYPE_UPDATE = 4;
    const TYPE_DELETE = 5;
    const TYPE_CALL = 10;
    const TYPE_AUTHENTICATE = 7;
    const TYPE_EVALUATE = 8;
    const TYPE_UPSERT = 9;
    const TYPE_PING = 64;
    const TYPE_JOIN = 65;
    const TYPE_SUBSCRIBE = 66;

    public function getType();
    public function getBody();
}

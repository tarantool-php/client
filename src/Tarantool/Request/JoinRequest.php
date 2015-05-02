<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

class JoinRequest extends Request
{
    private $serverUuid;

    public function __construct($serverUuid)
    {
        $this->serverUuid = $serverUuid;
    }

    public function getType()
    {
        return self::TYPE_JOIN;
    }

    public function getBody()
    {
        return [
            Iproto::SERVER_UUID => $this->serverUuid,
        ];
    }
}

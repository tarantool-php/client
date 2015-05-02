<?php

namespace Tarantool\Request;

use Tarantool\Iproto;

class SubscribeRequest extends Request
{
    private $clusterUuid;
    private $serverUuid;
    private $vclock;

    public function __construct($clusterUuid, $serverUuid, array $vclock)
    {
        $this->clusterUuid = $clusterUuid;
        $this->serverUuid = $serverUuid;
        $this->vclock = $vclock;
    }

    public function getType()
    {
        return self::TYPE_SUBSCRIBE;
    }

    public function getBody()
    {
        return [
            Iproto::CLUSTER_UUID => $this->clusterUuid,
            Iproto::SERVER_UUID => $this->serverUuid,
            Iproto::VCLOCK => $this->vclock,
        ];
    }
}

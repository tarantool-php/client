<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class SubscribeRequest implements Request
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
            IProto::CLUSTER_UUID => $this->clusterUuid,
            IProto::SERVER_UUID => $this->serverUuid,
            IProto::VCLOCK => $this->vclock,
        ];
    }
}

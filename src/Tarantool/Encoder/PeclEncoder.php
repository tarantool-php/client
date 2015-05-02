<?php

namespace Tarantool\Encoder;

use Tarantool\Exception\Exception;
use Tarantool\Iproto;
use Tarantool\Request\Request;
use Tarantool\Response;

class PeclEncoder implements Encoder
{
    private $unpacker;

    public function __construct()
    {
        $this->unpacker = new \MessagePackUnpacker();
        $this->unpacker->setOption(\MessagePack::OPT_PHPONLY, false);
    }

    public function encode(Request $request)
    {
        $content = pack('C*', 0x82,
            Iproto::CODE, $request->getType(),
            Iproto::SYNC, $request->getSync()
        );

        if (null !== $data = $request->getBody()) {
            $content .= msgpack_pack($data);
        }

        return msgpack_pack(strlen($content)).$content;
    }

    public function decode($data)
    {
        $this->unpacker->feed($data);

        if (!$this->unpacker->execute()) {
            throw new Exception('Bad response.');
        }

        $header = $this->unpacker->data();
        $code = $header[Iproto::CODE];
        $body = $this->unpacker->execute() ? $this->unpacker->data() : null;

        if ($code >= Request::TYPE_ERROR) {
            throw new Exception($body[Iproto::ERROR], $code & (Request::TYPE_ERROR - 1));
        }

        return new Response($header[Iproto::SYNC], $body ? $body[Iproto::DATA] : null);
    }
}

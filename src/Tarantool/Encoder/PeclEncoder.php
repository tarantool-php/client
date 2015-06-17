<?php

namespace Tarantool\Encoder;

use Tarantool\Exception\Exception;
use Tarantool\IProto;
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

    /**
     * {@inheritdoc}
     */
    public function encode(Request $request, $sync = null)
    {
        // @see https://github.com/msgpack/msgpack-php/issues/45
        $content = pack('C*', 0x82, IProto::CODE, $request->getType(), IProto::SYNC);
        $content .= msgpack_pack((int) $sync);

        if (null !== $data = $request->getBody()) {
            $content .= msgpack_pack($data);
        }

        return IProto::packLength(strlen($content)).$content;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        $this->unpacker->feed($data);

        if (!$this->unpacker->execute()) {
            throw new Exception('Unable to decode data.');
        }

        $header = $this->unpacker->data();
        if (!is_array($header)) {
            throw new Exception('Unable to decode data.');
        }

        $code = $header[IProto::CODE];
        $body = $this->unpacker->execute() ? $this->unpacker->data() : null;

        if ($code >= Request::TYPE_ERROR) {
            throw new Exception($body[IProto::ERROR], $code & (Request::TYPE_ERROR - 1));
        }

        return new Response($header[IProto::SYNC], $body ? $body[IProto::DATA] : null);
    }
}

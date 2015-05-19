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
        $content .= self::encodeInt((int) $sync);

        if (null !== $data = $request->getBody()) {
            $content .= msgpack_pack($data);
        }

        return msgpack_pack(strlen($content)).$content;
    }

    /**
     * {@inheritdoc}
     */
    public function decode($data)
    {
        $this->unpacker->feed($data);

        if (!$this->unpacker->execute()) {
            throw new Exception('Bad response.');
        }

        $header = $this->unpacker->data();
        $code = $header[IProto::CODE];
        $body = $this->unpacker->execute() ? $this->unpacker->data() : null;

        if ($code >= Request::TYPE_ERROR) {
            throw new Exception($body[IProto::ERROR], $code & (Request::TYPE_ERROR - 1));
        }

        return new Response($header[IProto::SYNC], $body ? $body[IProto::DATA] : null);
    }

    private static function encodeInt($value)
    {
        if ($value <= 127) {
            return pack('C', $value);
        }
        if ($value <= 255) {
            return pack('CC', 0xcc, $value);
        }
        if ($value <= 0xffff) {
            return pack('Cn', 0xcd, $value);
        }
        if ($value <= 0xffffffff) {
            return pack('CN', 0xce, $value);
        }

        // The "J" code is only available as of PHP 5.6.3
        $h = ($value & 0xffffffff00000000) >> 32;
        $l = $value & 0xffffffff;

        return pack('CNN', 0xcf, $h, $l);
    }
}

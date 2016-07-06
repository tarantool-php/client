<?php

namespace Tarantool\Client\Packer;

use Tarantool\Client\Exception\Exception;
use Tarantool\Client\IProto;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

class PeclPacker implements Packer
{
    private $packer;
    private $unpacker;

    public function __construct($phpOnly = true)
    {
        $this->packer = new \MessagePack($phpOnly);
        $this->unpacker = new \MessagePackUnpacker($phpOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function pack(Request $request, $sync = null)
    {
        // @see https://github.com/msgpack/msgpack-php/issues/45
        $content = pack('C*', 0x82, IProto::CODE, $request->getType(), IProto::SYNC);
        $content .= $this->packer->pack((int) $sync);

        if (null !== $body = $request->getBody()) {
            $content .= $this->packer->pack($body);
        }

        return PackUtils::packLength(strlen($content)).$content;
    }

    /**
     * {@inheritdoc}
     */
    public function unpack($data)
    {
        $this->unpacker->feed($data);

        if (!$this->unpacker->execute()) {
            throw new Exception('Unable to unpack data.');
        }

        $header = $this->unpacker->data();

        if (!$this->unpacker->execute()) {
            throw new Exception('Unable to unpack data.');
        }

        $body = (array) $this->unpacker->data();
        $code = $header[IProto::CODE];

        if ($code >= Response::TYPE_ERROR) {
            throw new Exception($body[IProto::ERROR], $code & (Response::TYPE_ERROR - 1));
        }

        return new Response(
            $header[IProto::SYNC],
            isset($body[IProto::DATA]) ? $body[IProto::DATA] : null
        );
    }
}

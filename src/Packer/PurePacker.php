<?php

namespace Tarantool\Client\Packer;

use MessagePack\BufferUnpacker;
use MessagePack\Packer as MsgPacker;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\IProto;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

class PurePacker implements Packer
{
    private $packer;
    private $unpacker;

    public function __construct(MsgPacker $packer = null, BufferUnpacker $unpacker = null)
    {
        $this->packer = $packer ?: new MsgPacker();
        $this->unpacker = $unpacker ?: new BufferUnpacker();
    }

    public function pack(Request $request, $sync = null)
    {
        $content = $this->packer->packMapHeader(2).
            $this->packer->packInt(IProto::CODE).
            $this->packer->packInt($request->getType()).
            $this->packer->packInt(IProto::SYNC).
            $this->packer->packInt($sync);

        if (null !== $body = $request->getBody()) {
            $content .= $this->packer->packMap($body);
        }

        return PackUtils::packLength(strlen($content)).$content;
    }

    public function unpack($data)
    {
        try {
            $this->unpacker->reset($data);
            $header = $this->unpacker->unpackMap();
            $body = $this->unpacker->unpackMap();
        } catch (\Exception $e) {
            throw new Exception('Unable to unpack data.', 0, $e);
        } catch (\Throwable $e) {
            throw new Exception('Unable to unpack data.', 0, $e);
        }

        $code = $header[IProto::CODE];

        if ($code >= Response::TYPE_ERROR) {
            throw new Exception($body[IProto::ERROR], $code & (Response::TYPE_ERROR - 1));
        }

        return new Response($header[IProto::SYNC], $body ? $body[IProto::DATA] : null);
    }
}

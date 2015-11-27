<?php

namespace Tarantool\Packer;

use MessagePack\BufferUnpacker;
use MessagePack\Packer as MessagePackPacker;
use Tarantool\Exception\Exception;
use Tarantool\IProto;
use Tarantool\Request\Request;
use Tarantool\Response;

class PurePacker implements Packer
{
    private $packer;
    private $unpacker;

    public function __construct()
    {
        $this->packer = new MessagePackPacker();
        $this->unpacker = new BufferUnpacker();
    }

    public function pack(Request $request, $sync = null)
    {
        $content = $this->packer->packMap([
            IProto::CODE => $request->getType(),
            IProto::SYNC => (int) $sync,
        ]);

        if (null !== $data = $request->getBody()) {
            $content .= $this->packer->pack($data);
        }

        return PackUtils::packLength(strlen($content)).$content;
    }

    public function unpack($data)
    {
        $message = $this->unpacker->reset($data)->tryUnpack();

        if (2 !== count($message)) {
            throw new Exception('Unable to unpack data.');
        }

        list($header, $body) = $message;

        $code = $header[IProto::CODE];

        if ($code >= Request::TYPE_ERROR) {
            throw new Exception($body[IProto::ERROR], $code & (Request::TYPE_ERROR - 1));
        }

        return new Response($header[IProto::SYNC], $body ? $body[IProto::DATA] : null);
    }
}

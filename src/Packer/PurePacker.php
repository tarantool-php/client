<?php

namespace Tarantool\Client\Packer;

use MessagePack\BufferUnpacker;
use MessagePack\Packer as MessagePackPacker;
use Tarantool\Client\Exception\Exception;
use Tarantool\Client\IProto;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

class PurePacker implements Packer
{
    private $packer;
    private $bufferUnpacker;

    public function __construct(MessagePackPacker $packer = null, BufferUnpacker $bufferUnpacker = null)
    {
        $this->packer = $packer ?: new MessagePackPacker();
        $this->bufferUnpacker = $bufferUnpacker ?: new BufferUnpacker();
    }

    public function pack(Request $request, $sync = null)
    {
        $content = $this->packer->packMap([
            IProto::CODE => $request->getType(),
            IProto::SYNC => (int) $sync,
        ]);

        if (null !== $body = $request->getBody()) {
            $content .= $this->packer->packMap($body);
        }

        return PackUtils::packLength(strlen($content)).$content;
    }

    public function unpack($data)
    {
        $message = $this->bufferUnpacker->reset($data)->tryUnpack();

        if (2 !== count($message)) {
            throw new Exception('Unable to unpack data.');
        }

        list($header, $body) = $message;

        $code = $header[IProto::CODE];

        if ($code >= Response::TYPE_ERROR) {
            throw new Exception($body[IProto::ERROR], $code & (Response::TYPE_ERROR - 1));
        }

        return new Response($header[IProto::SYNC], $body ? $body[IProto::DATA] : null);
    }
}

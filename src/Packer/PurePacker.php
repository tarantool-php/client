<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Packer;

use MessagePack\BufferUnpacker;
use MessagePack\Packer as MsgPacker;
use Tarantool\Client\Exception\PackerException;
use Tarantool\Client\IProto;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class PurePacker implements Packer
{
    private $packer;
    private $unpacker;

    public function __construct(MsgPacker $packer = null, BufferUnpacker $unpacker = null)
    {
        $this->packer = $packer ?: new MsgPacker();
        $this->unpacker = $unpacker ?: new BufferUnpacker();
    }

    public function pack(Request $request, int $sync = null) : string
    {
        $content = $this->packer->packMapHeader(2).
            $this->packer->packInt(IProto::CODE).
            $this->packer->packInt($request->getType()).
            $this->packer->packInt(IProto::SYNC).
            $this->packer->packInt($sync ?: 0).
            $this->packer->packMap($request->getBody());

        return PackUtils::packLength(\strlen($content)).$content;
    }

    public function unpack(string $data) : Response
    {
        try {
            $this->unpacker->reset($data);

            return new Response(
                $this->unpacker->unpackMap(),
                $this->unpacker->unpackMap()
            );
        } catch (\Throwable $e) {
            throw new PackerException('Unable to unpack data.', 0, $e);
        }
    }
}

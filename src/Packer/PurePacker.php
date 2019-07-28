<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Packer;

use MessagePack\BufferUnpacker;
use MessagePack\Exception\UnpackingFailedException;
use MessagePack\Packer;
use Tarantool\Client\Exception\UnpackingFailed;
use Tarantool\Client\Keys;
use Tarantool\Client\Packer\Packer as ClientPacker;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class PurePacker implements ClientPacker
{
    private $packer;
    private $unpacker;

    public function __construct(?Packer $packer = null, ?BufferUnpacker $unpacker = null)
    {
        $this->packer = $packer ?: new Packer();
        $this->unpacker = $unpacker ?: new BufferUnpacker();
    }

    public function pack(Request $request, int $sync) : string
    {
        $content = $this->packer->packMapHeader(2).
            $this->packer->packInt(Keys::CODE).
            $this->packer->packInt($request->getType()).
            $this->packer->packInt(Keys::SYNC).
            $this->packer->packInt($sync).
            $this->packer->packMap($request->getBody());

        return PacketLength::pack(\strlen($content)).$content;
    }

    public function unpack(string $data) : Response
    {
        try {
            $this->unpacker->reset($data);

            return new Response(
                $this->unpacker->unpackMap(),
                $this->unpacker->unpackMap()
            );
        } catch (UnpackingFailedException $e) {
            throw new UnpackingFailed('Unable to unpack response.', 0, $e);
        }
    }
}

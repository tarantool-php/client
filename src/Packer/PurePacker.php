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
use MessagePack\Packer;
use MessagePack\PackOptions;
use MessagePack\TypeTransformer\Extension;
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
        $this->packer = $packer ?: new Packer(PackOptions::FORCE_STR);
        $this->unpacker = $unpacker ?: new BufferUnpacker();
    }

    public static function fromExtensions(Extension $extension, Extension ...$extensions) : self
    {
        $extensions = [-1 => $extension] + $extensions;

        return new self(
            new Packer(PackOptions::FORCE_STR, $extensions),
            new BufferUnpacker('', null, $extensions)
        );
    }

    public function pack(Request $request, int $sync) : string
    {
        // hot path optimization
        $packet = \pack('C*', 0x82, Keys::CODE, $request->getType(), Keys::SYNC).
            $this->packer->packInt($sync).
            $this->packer->packMap($request->getBody());

        return PacketLength::pack(\strlen($packet)).$packet;
    }

    public function unpack(string $packet) : Response
    {
        $this->unpacker->reset($packet);

        return new Response(
            $this->unpacker->unpackMap(),
            $this->unpacker->unpackMap()
        );
    }
}

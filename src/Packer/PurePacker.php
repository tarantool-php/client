<?php

/**
 * This file is part of the tarantool/client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Packer;

use MessagePack\BufferUnpacker;
use MessagePack\Extension;
use MessagePack\Packer;
use MessagePack\PackOptions;
use MessagePack\UnpackOptions;
use Tarantool\Client\Keys;
use Tarantool\Client\Packer\Extension\DecimalExtension;
use Tarantool\Client\Packer\Extension\ErrorExtension;
use Tarantool\Client\Packer\Extension\UuidExtension;
use Tarantool\Client\Packer\Packer as ClientPacker;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class PurePacker implements ClientPacker
{
    /** @var Packer */
    private $packer;

    /** @var BufferUnpacker */
    private $unpacker;

    public function __construct(?Packer $packer = null, ?BufferUnpacker $unpacker = null)
    {
        $this->packer = $packer ?: new Packer(PackOptions::FORCE_STR);
        $this->unpacker = $unpacker ?: new BufferUnpacker('', \extension_loaded('decimal') ? UnpackOptions::BIGINT_AS_DEC : null);
    }

    public static function fromExtensions(Extension $extension, Extension ...$extensions) : self
    {
        $extensions = [-1 => $extension] + $extensions;

        return new self(
            new Packer(PackOptions::FORCE_STR, $extensions),
            new BufferUnpacker('', \extension_loaded('decimal') ? UnpackOptions::BIGINT_AS_DEC : null, $extensions)
        );
    }

    public static function fromAvailableExtensions() : self
    {
        $extensions = [new UuidExtension(), new ErrorExtension()];
        if (\extension_loaded('decimal')) {
            $extensions[] = new DecimalExtension();

            return new self(
                new Packer(PackOptions::FORCE_STR, $extensions),
                new BufferUnpacker('', UnpackOptions::BIGINT_AS_DEC, $extensions)
            );
        }

        return new self(
            new Packer(PackOptions::FORCE_STR, $extensions),
            new BufferUnpacker('', null, $extensions)
        );
    }

    public function pack(Request $request, int $sync) : string
    {
        // Hot path optimization
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

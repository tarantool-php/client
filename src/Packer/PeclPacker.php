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

use Tarantool\Client\Keys;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class PeclPacker implements Packer
{
    /** @var \MessagePack */
    private $packer;

    /** @var \MessagePackUnpacker */
    private $unpacker;

    public function __construct(bool $phpOnly = true)
    {
        $this->packer = new \MessagePack($phpOnly);
        $this->unpacker = new \MessagePackUnpacker($phpOnly);
    }

    public function pack(Request $request, int $sync) : string
    {
        // @see https://github.com/msgpack/msgpack-php/issues/45
        $packet = \pack('C*', 0x82, Keys::CODE, $request->getType(), Keys::SYNC).
            $this->packer->pack($sync).
            $this->packer->pack($request->getBody());

        return PacketLength::pack(\strlen($packet)).$packet;
    }

    public function unpack(string $packet) : Response
    {
        $this->unpacker->feed($packet);

        if (!$this->unpacker->execute()) {
            throw new \RuntimeException('Unable to unpack response header.');
        }
        $header = $this->unpacker->data();

        if (!$this->unpacker->execute()) {
            throw new \RuntimeException('Unable to unpack response body.');
        }
        $body = $this->unpacker->data();

        // with PHP_ONLY = true an empty array
        // will be unpacked to stdClass
        if ($body instanceof \stdClass) {
            $body = (array) $body;
        }

        return new Response($header, $body);
    }
}

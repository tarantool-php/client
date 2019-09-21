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
    private $phpOnly;
    private $packer;

    public function __construct(bool $phpOnly = true)
    {
        $this->phpOnly = $phpOnly;
        $this->packer = new \MessagePack($phpOnly);
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
        // @see https://github.com/msgpack/msgpack-php/issues/139
        // $unpacker = clone $this->unpacker;
        $unpacker = new \MessagePackUnpacker($this->phpOnly);
        $unpacker->feed($packet);

        if (!$unpacker->execute()) {
            throw new \UnexpectedValueException('Unable to unpack response header.');
        }

        return new Response($unpacker->data(),
            static function () use ($unpacker) {
                if (!$unpacker->execute()) {
                    throw new \UnexpectedValueException('Unable to unpack response body.');
                }

                $body = $unpacker->data();
                // with PHP_ONLY = true an empty array
                // will be unpacked to stdClass
                return $body instanceof \stdClass
                    ? (array) $body
                    : $body;
            }
        );
    }
}

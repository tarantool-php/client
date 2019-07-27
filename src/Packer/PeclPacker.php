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

use Tarantool\Client\Exception\UnpackingFailed;
use Tarantool\Client\Keys;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class PeclPacker implements Packer
{
    private $packer;
    private $unpacker;

    public function __construct(bool $phpOnly = true)
    {
        $this->packer = new \MessagePack($phpOnly);
        $this->unpacker = new \MessagePackUnpacker($phpOnly);
    }

    public function pack(Request $request, int $sync = 0) : string
    {
        // @see https://github.com/msgpack/msgpack-php/issues/45
        $content = \pack('C*', 0x82, Keys::CODE, $request->getType(), Keys::SYNC).
            $this->packer->pack($sync).
            $this->packer->pack($request->getBody());

        return PacketLength::pack(\strlen($content)).$content;
    }

    public function unpack(string $data) : Response
    {
        $this->unpacker->feed($data);

        if (!$this->unpacker->execute()) {
            throw UnpackingFailed::invalidResponse();
        }

        $header = $this->unpacker->data();
        if (!\is_array($header)) {
            throw UnpackingFailed::invalidResponse();
        }

        if (!$this->unpacker->execute()) {
            throw UnpackingFailed::invalidResponse();
        }

        $body = $this->unpacker->data();
        if (\is_array($body)) {
            return new Response($header, $body);
        }
        if ($body instanceof \stdClass) {
            return new Response($header, (array) $body);
        }

        throw UnpackingFailed::invalidResponse();
    }
}

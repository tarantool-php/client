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

use Tarantool\Client\Exception\UnpackingFailed;
use Tarantool\Client\IProto;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class Pecl implements Packer
{
    private $packer;
    private $unpacker;

    public function __construct()
    {
        $this->packer = new \MessagePack(false);
        $this->unpacker = new \MessagePackUnpacker(false);
    }

    public function pack(Request $request, int $sync = null) : string
    {
        // @see https://github.com/msgpack/msgpack-php/issues/45
        $content = \pack('C*', 0x82, IProto::CODE, $request->getType(), IProto::SYNC).
            $this->packer->pack($sync ?: 0).
            $this->packer->pack($request->getBody());

        return PackUtils::packLength(\strlen($content)).$content;
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
        if (!\is_array($body)) {
            throw UnpackingFailed::invalidResponse();
        }

        return new Response($header, $body);
    }
}

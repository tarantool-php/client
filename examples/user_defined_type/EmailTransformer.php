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

namespace App;

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\TypeTransformer\Packable;
use MessagePack\TypeTransformer\Unpackable;

final class EmailTransformer implements Packable, Unpackable
{
    private $type;

    public function __construct(int $type)
    {
        $this->type = $type;
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function pack(Packer $packer, $value) : ?string
    {
        if (!$value instanceof Email) {
            return null;
        }

        return $packer->packExt($this->type, $packer->packStr($value->toString()));
    }

    public function unpack(BufferUnpacker $unpacker, int $extLength) : Email
    {
        return new Email($unpacker->unpackStr());
    }
}

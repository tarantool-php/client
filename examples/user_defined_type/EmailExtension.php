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

namespace App;

use MessagePack\BufferUnpacker;
use MessagePack\Extension;
use MessagePack\Packer;

final class EmailExtension implements Extension
{
    public function __construct(private readonly int $type)
    {
    }

    #[\Override]
    public function getType() : int
    {
        return $this->type;
    }

    #[\Override]
    public function pack(Packer $packer, mixed $value) : ?string
    {
        if (!$value instanceof Email) {
            return null;
        }

        return $packer->packExt($this->type,
            $packer->packStr($value->toString())
        );
    }

    #[\Override]
    public function unpackExt(BufferUnpacker $unpacker, int $extLength) : Email
    {
        return new Email($unpacker->unpackStr());
    }
}

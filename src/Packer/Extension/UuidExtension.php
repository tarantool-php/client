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

namespace Tarantool\Client\Packer\Extension;

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\TypeTransformer\Extension;
use Symfony\Component\Uid\Uuid;

final class UuidExtension implements Extension
{
    private const TYPE = 2;

    public function getType() : int
    {
        return self::TYPE;
    }

    /**
     * @param object $value
     */
    public function pack(Packer $packer, $value) : ?string
    {
        if (!$value instanceof Uuid) {
            return null;
        }

        return $packer->packExt(self::TYPE, $value->toBinary());
    }

    /**
     * @return Uuid
     */
    public function unpackExt(BufferUnpacker $unpacker, int $extLength)
    {
        return Uuid::fromString($unpacker->read($extLength));
    }
}

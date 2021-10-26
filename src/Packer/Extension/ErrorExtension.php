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

namespace Tarantool\Client\Packer\Extension;

use MessagePack\BufferUnpacker;
use MessagePack\Extension;
use MessagePack\Packer;
use Tarantool\Client\Error;
use Tarantool\Client\Keys;

final class ErrorExtension implements Extension
{
    private const TYPE = 3;

    public function getType() : int
    {
        return self::TYPE;
    }

    public function pack(Packer $packer, $value) : ?string
    {
        if (!$value instanceof Error) {
            return null;
        }

        [Keys::ERROR_STACK => $errorStack] = $value->toMap();

        $packedError = $packer->packMapHeader(1);
        $packedError .= $packer->packInt(Keys::ERROR_STACK);
        $packedError .= $packer->packArrayHeader(\count($errorStack));
        foreach ($errorStack as $error) {
            $packedError .= $packer->packMap($error);
        }

        return $packer->packExt(self::TYPE, $packedError);
    }

    /**
     * @return Error
     */
    public function unpackExt(BufferUnpacker $unpacker, int $extLength)
    {
        return Error::fromMap($unpacker->unpackMap());
    }
}

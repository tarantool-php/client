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

use Decimal\Decimal;
use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\TypeTransformer\Extension;

class DecimalExtension implements Extension
{
    private const TYPE = 1;
    private const PRECISION = 38;

    public function getType() : int
    {
        return self::TYPE;
    }

    public function pack(Packer $packer, $value) : ?string
    {
        if (!$value instanceof Decimal) {
            return null;
        }

        // @see https://github.com/php-decimal/ext-decimal/issues/22#issuecomment-512364914
        $data = $value->toFixed(self::PRECISION);

        if ('-' === $data[0]) {
            $nibble = 'd';
            $data = \substr($data, 1);
        } else {
            $nibble = 'c';
        }

        if ($scale = \strpos($data, '.')) {
            $data = \substr_replace($data, '', $scale, 1);
        }

        $data = (0 === \strlen($data) % 2) ? "0{$data}{$nibble}" : "{$data}{$nibble}";

        return $packer->packExt(self::TYPE,
            $packer->packInt($scale).\hex2bin($data)
        );
    }

    public function unpackExt(BufferUnpacker $unpacker, int $extLength)
    {
        $scale = $unpacker->unpackInt();
        $data = $unpacker->read($extLength - 1);
        $data = \bin2hex($data);

        $sign = 'd' === $data[-1] ? '-' : '';
        $dec = \substr($data, 0, -1);

        if (0 !== $scale) {
            $length = \strlen($dec);
            $dec = ($length <= $scale)
                ? \substr_replace($dec, '0.'.\str_repeat('0', $scale - $length), -$scale, 0)
                : \substr_replace($dec, '.', -$scale, 0);
        }

        return new Decimal($sign.$dec, self::PRECISION);
    }
}

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

use Decimal\Decimal;
use MessagePack\BufferUnpacker;
use MessagePack\Extension;
use MessagePack\Packer;

final class DecimalExtension implements Extension
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

        $pieces = \explode('.', $data, 2);
        $pieces[1] = \rtrim($pieces[1], '0');

        $data = "{$pieces[0]}{$pieces[1]}{$nibble}";
        if (0 !== \strlen($data) % 2) {
            $data = '0'.$data;
        }

        return $packer->packExt(self::TYPE,
            $packer->packInt('' === $pieces[1] ? 0 : \strlen($pieces[1])).\hex2bin($data)
        );
    }

    /**
     * @return Decimal
     */
    public function unpackExt(BufferUnpacker $unpacker, int $extLength)
    {
        /**
         * @psalm-suppress UndefinedDocblockClass (suppresses \GMP)
         * @var int $scale
         */
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

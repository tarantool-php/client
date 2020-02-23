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

use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use MessagePack\PackOptions;
use MessagePack\UnpackOptions;
use Tarantool\Client\Packer\Extension\DecimalExtension;
use Tarantool\Client\Packer\Packer as ClientPacker;

final class PackerFactory
{
    public static function create() : ClientPacker
    {
        if (\class_exists(BufferUnpacker::class)) {
            if (!\extension_loaded('decimal')) {
                return new PurePacker();
            }

            $extensions = [new DecimalExtension()];

            return new PurePacker(
                new Packer(PackOptions::FORCE_STR, $extensions),
                new BufferUnpacker('', UnpackOptions::BIGINT_AS_DEC, $extensions)
            );
        }

        if (\extension_loaded('msgpack')) {
            return new PeclPacker();
        }

        throw new \Error('None of the supported msgpack packages were found. To install one, run "composer require rybakit/msgpack"');
    }
}

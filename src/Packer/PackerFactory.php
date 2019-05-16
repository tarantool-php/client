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

final class PackerFactory
{
    public static function create() : Packer
    {
        if (\class_exists(BufferUnpacker::class)) {
            return new PurePacker();
        }

        if (\extension_loaded('msgpack')) {
            return new PeclPacker();
        }

        throw new \Error('None of the supported msgpack packages were found. To install one, run "composer require rybakit/msgpack".');
    }
}

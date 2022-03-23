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

namespace Tarantool\Client\Tests;

use Tarantool\Client\Packer\Extension\ErrorExtension;
use Tarantool\Client\Packer\PurePacker;

final class PackerDataProvider
{
    public static function providePurePackerWithDefaultSettings() : iterable
    {
        return [
            [new PurePacker()],
            [PurePacker::fromAvailableExtensions()],
            [PurePacker::fromExtensions(new ErrorExtension())],
        ];
    }
}

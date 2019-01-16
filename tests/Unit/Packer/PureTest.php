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

namespace Tarantool\Client\Tests\Unit\Packer;

use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\Pure;

/**
 * @requires function MessagePack\Packer::pack
 */
final class PureTest extends PackerTest
{
    protected function createPacker() : Packer
    {
        return new Pure();
    }
}

<?php

namespace Tarantool\Tests\Unit\Packer;

use Tarantool\Packer\PurePacker;

/**
 * @requires function MessagePack\Packer::pack
 */
class PurePackerTest extends PackerTest
{
    protected function createPacker()
    {
        return new PurePacker();
    }
}

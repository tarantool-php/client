<?php

namespace Tarantool\Client\Tests\Unit\Packer;

use Tarantool\Client\Packer\PurePacker;

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

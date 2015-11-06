<?php

namespace Tarantool\Tests\Unit\Packer;

use Tarantool\Packer\PurePacker;

class PurePackerTest extends PackerTest
{
    protected function createPacker()
    {
        return new PurePacker();
    }
}

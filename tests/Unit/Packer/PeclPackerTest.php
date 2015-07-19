<?php

namespace Tarantool\Tests\Unit\Packer;

use Tarantool\Packer\PeclPacker;

class PeclPackerTest extends PackerTest
{
    protected function createPacker()
    {
        return new PeclPacker();
    }
}

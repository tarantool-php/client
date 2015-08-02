<?php

namespace Tarantool\Tests\Unit\Packer;

use Tarantool\Packer\PeclPacker;

/**
 * @requires extension msgpack
 * @requires function MessagePackUnpacker::__construct
 */
class PeclPackerTest extends PackerTest
{
    protected function createPacker()
    {
        return new PeclPacker();
    }
}

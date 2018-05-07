<?php

namespace Tarantool\Client\Tests\Unit\Packer;

use Tarantool\Client\Packer\PeclPacker;

/**
 * @requires extension msgpack
 */
class PeclPackerTest extends PackerTest
{
    protected function createPacker()
    {
        return new PeclPacker();
    }
}

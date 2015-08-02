<?php

namespace Tarantool\Tests\Unit\Packer;

use Tarantool\Packer\PeclLitePacker;

/**
 * @requires extension msgpack
 */
class PeclLitePackerTest extends PackerTest
{
    protected function createPacker()
    {
        return new PeclLitePacker();
    }
}

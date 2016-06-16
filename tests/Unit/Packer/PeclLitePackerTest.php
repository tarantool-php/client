<?php

namespace Tarantool\Client\Tests\Unit\Packer;

use Tarantool\Client\Packer\PeclLitePacker;

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

<?php

namespace Tarantool\Tests\Unit\Encoder;

use Tarantool\Encoder\PeclEncoder;

class PeclEncoderTest extends EncoderTest
{
    protected function createEncoder()
    {
        return new PeclEncoder();
    }
}

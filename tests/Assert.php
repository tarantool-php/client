<?php

namespace Tarantool\Tests;

trait Assert
{
    protected function assertResponse($response)
    {
        $this->assertInstanceOf('Tarantool\Response', $response);
    }
}

<?php

namespace Tarantool\Encoder;

use Tarantool\Request\Request;

interface Encoder
{
    /**
     * @param Request $request
     *
     * @return string
     */
    public function encode(Request $request);

    /**
     * @param string $data
     *
     * @return \Tarantool\Response
     */
    public function decode($data);
}

<?php

namespace Tarantool\Encoder;

use Tarantool\Request\Request;

interface Encoder
{
    /**
     * @param Request  $request
     * @param int|null $sync
     *
     * @return string
     */
    public function encode(Request $request, $sync = null);

    /**
     * @param string $data
     *
     * @return \Tarantool\Response
     */
    public function decode($data);
}

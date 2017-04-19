<?php

namespace Tarantool\Client\Packer;

use Tarantool\Client\Request\Request;

interface Packer
{
    /**
     * @param Request $request
     * @param int|null $sync
     *
     * @return string
     */
    public function pack(Request $request, $sync = null);

    /**
     * @param string $data
     *
     * @return \Tarantool\Client\Response
     *
     * @throws \Tarantool\Client\Exception\Exception
     */
    public function unpack($data);
}

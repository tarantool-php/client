<?php

namespace Tarantool\Packer;

use Tarantool\Request\Request;

interface Packer
{
    /**
     * @param Request  $request
     * @param int|null $sync
     *
     * @return string
     */
    public function pack(Request $request, $sync = null);

    /**
     * @param string $data
     *
     * @return \Tarantool\Response
     */
    public function unpack($data);
}

<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tarantool\Client\Tests\Unit\Packer;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Keys;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\RequestTypes;

abstract class PackerTest extends TestCase
{
    /**
     * @var Packer
     */
    protected $packer;

    final protected function setUp() : void
    {
        $this->packer = $this->createPacker();
    }

    public function provideBadUnpackData() : iterable
    {
        return [
            [''],
            ["\x82"],
            [\pack('C*', 0x82, Keys::CODE, RequestTypes::CALL, Keys::SYNC, 0)."\x82"],
        ];
    }

    abstract protected function createPacker() : Packer;
}

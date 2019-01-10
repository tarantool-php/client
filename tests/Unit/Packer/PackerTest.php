<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Tests\Unit\Packer;

use PHPUnit\Framework\TestCase;
use Tarantool\Client\Exception\PackerException;
use Tarantool\Client\Packer\Packer;

abstract class PackerTest extends TestCase
{
    /**
     * @var Packer
     */
    private $packer;

    protected function setUp() : void
    {
        $this->packer = $this->createPacker();
    }

    /**
     * @dataProvider provideBadUnpackData
     */
    public function testThrowExceptionOnBadUnpackData(string $data) : void
    {
        $this->expectException(PackerException::class);
        $this->expectExceptionMessage('Unable to unpack data.');

        $this->packer->unpack($data);
    }

    public function provideBadUnpackData() : iterable
    {
        return [
            ['foobar'],
            ["\0"],
        ];
    }

    abstract protected function createPacker() : Packer;
}

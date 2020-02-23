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

use Tarantool\Client\Keys;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PeclPacker;

/**
 * @requires extension msgpack
 */
final class PeclPackerTest extends PackerTest
{
    protected function createPacker() : Packer
    {
        return new PeclPacker();
    }

    /**
     * @dataProvider provideBadUnpackData
     */
    public function testThrowExceptionOnBadUnpackData(string $data) : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/Unable to unpack response (header|body)/');

        $this->packer->unpack($data)->tryGetBodyField(Keys::DATA);
    }
}

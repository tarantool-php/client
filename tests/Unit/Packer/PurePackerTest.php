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

use MessagePack\Exception\UnpackingFailedException;
use Tarantool\Client\Keys;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;

final class PurePackerTest extends PackerTest
{
    protected function createPacker() : Packer
    {
        return new PurePacker();
    }

    /**
     * @dataProvider provideBadUnpackData
     */
    public function testThrowExceptionOnBadUnpackData(string $data) : void
    {
        $this->expectException(UnpackingFailedException::class);

        $this->packer->unpack($data)->tryGetBodyField(Keys::DATA);
    }
}

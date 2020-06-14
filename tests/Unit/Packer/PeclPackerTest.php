<?php

/**
 * This file is part of the tarantool/client package.
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
use Tarantool\Client\Tests\PhpUnitCompat;

final class PeclPackerTest extends PackerTest
{
    use PhpUnitCompat;

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
        $this->expectExceptionMessageMatches('/Unable to unpack response (header|body)/');

        $this->packer->unpack($data)->tryGetBodyField(Keys::DATA);
    }
}

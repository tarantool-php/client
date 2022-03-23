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

use MessagePack\Exception\UnpackingFailedException;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Keys;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\RequestTypes;

final class PurePackerTest extends TestCase
{
    /** @var Packer */
    private $packer;

    protected function setUp() : void
    {
        $this->packer = new PurePacker();
    }

    /**
     * @dataProvider provideBadUnpackData
     */
    public function testThrowExceptionOnBadUnpackData(string $data) : void
    {
        $this->expectException(UnpackingFailedException::class);

        $this->packer->unpack($data)->tryGetBodyField(Keys::DATA);
    }

    public function provideBadUnpackData() : iterable
    {
        return [
            [''],
            ["\x82"],
            [\pack('C*', 0x82, Keys::CODE, RequestTypes::CALL, Keys::SYNC, 0)."\x82"],
        ];
    }
}

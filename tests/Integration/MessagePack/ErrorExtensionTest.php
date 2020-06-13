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

namespace Tarantool\Client\Tests\Integration\MessagePack;

use Tarantool\Client\Error;
use Tarantool\Client\Packer\Extension\ErrorExtension;
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Tests\Integration\ClientBuilder;
use Tarantool\Client\Tests\Integration\TestCase;

/**
 * @requires Tarantool >=2.4.1
 * @requires clientPacker pure
 */
final class ErrorExtensionTest extends TestCase
{
    public function testPackingAndUnpacking() : void
    {
        $client = ClientBuilder::createFromEnv()
            ->setPackerPureFactory(static function () {
                return PurePacker::fromExtensions(new ErrorExtension());
            })
            ->build();

        /** @var Error $error */
        $error = $client->evaluate('
            box.session.settings.error_marshaling_enabled = true
            err1 = box.error.new({code = 1, type = "t1", reason = "r1"})
            err2 = box.error.new({code = 2, type = "t2", reason = "r2",})
            err1:set_prev(err2)
            return err1
        ')[0];

        self::assertInstanceOf(Error::class, $error);
        self::assertSame('CustomError', $error->getType());
        self::assertSame('r1', $error->getMessage());
        self::assertSame(1, $error->getCode());
        self::assertSame(['custom_type' => 't1'], $error->getFields());

        $prevError = $error->getPrevious();
        self::assertInstanceOf(Error::class, $prevError);
        self::assertSame('CustomError', $prevError->getType());
        self::assertSame('r2', $prevError->getMessage());
        self::assertSame(2, $prevError->getCode());
        self::assertSame(['custom_type' => 't2'], $prevError->getFields());
        self::assertNull($prevError->getPrevious());

        $isEqual = $client->evaluate('
            box.session.settings.error_marshaling_enabled = true
            err1 = ...
            err2 = err1.prev
            return
                err1.code == 1 and err1.type == "t1" and err1.message == "r1" and
                err2.code == 2 and err2.type == "t2" and err2.message == "r2" and
                err1.prev == err2
        ', $error)[0];

        self::assertTrue($isEqual);
    }
}

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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Error;
use Tarantool\Client\Exception\RequestFailed;

final class BoxErrorTest extends TestCase
{
    public function testExceptionIsThrown() : void
    {
        $this->expectException(RequestFailed::class);
        $this->expectExceptionMessage('Because I can');
        $this->expectExceptionCode(42);

        $this->client->evaluate('box.error({code = 42, reason = "Because I can"})');
    }

    /**
     * @requires Tarantool >=2.4.1
     */
    public function testExceptionWithErrorIsThrown() : void
    {
        try {
            /*
             * Triggers "AccessDeniedError", which includes the fields
             * "object_type", "object_name", "access_type", "user" (since 3.1).
             * See https://www.tarantool.io/en/doc/2.4/dev_guide/internals/box_protocol/#binary-protocol-responses-for-errors-extra.
             */
            $this->client->evaluate("
                local temp_user = 'user_with_no_privileges'
                local curr_user = box.session.user()
                box.schema.user.create(temp_user)
                box.session.su(temp_user)
                local _, err = pcall(box.schema.user.grant, temp_user, 'execute', 'universe')
                box.session.su(curr_user)
                box.schema.user.drop(temp_user)
                box.error(err)
            ");
            self::fail(sprintf('"%s" exception was not thrown', RequestFailed::class));
        } catch (RequestFailed $e) {
            self::assertSame("Write access to space '_priv' is denied for user 'user_with_no_privileges'", $e->getMessage());
            self::assertSame(42, $e->getCode());

            $error = $e->getError();
            self::assertInstanceOf(Error::class, $error);
            self::assertSame('AccessDeniedError', $error->getType());
            self::assertSame("Write access to space '_priv' is denied for user 'user_with_no_privileges'", $error->getMessage());
            self::assertSame(42, $error->getCode());
            $expectedFields = [
                'object_type' => 'space',
                'object_name' => '_priv',
                'access_type' => 'Write',
                'user' => 'user_with_no_privileges',
            ];
            if ($this->tarantoolVersionSatisfies('< 3.1')) {
                unset($expectedFields['user']);
            }
            self::assertEquals($expectedFields, $error->getFields());
            self::assertNull($error->getPrevious());
        }
    }

    /**
     * @requires Tarantool >=2.4.1
     */
    public function testExceptionWithNestedErrorIsThrown() : void
    {
        try {
            $this->client->evaluate('
                err1 = box.error.new({code = 1, type = "t1", reason = "r1"})
                err2 = box.error.new({code = 2, type = "t2", reason = "r2",})
                err1:set_prev(err2)
                box.error(err1)
            ');
            self::fail(sprintf('"%s" exception was not thrown', RequestFailed::class));
        } catch (RequestFailed $e) {
            self::assertSame('r1', $e->getMessage());
            self::assertSame(1, $e->getCode());

            $error = $e->getError();
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
        }
    }
}

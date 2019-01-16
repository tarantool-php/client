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

namespace Tarantool\Client\Tests\Integration;

use Tarantool\Client\Exception\RequestFailed;

final class ClientSpaceCachingTest extends TestCase
{
    public function testCacheSpace() : void
    {
        $total = self::getTotalSelectCalls();

        $this->client->flushSpaces();
        $this->client->getSpace('space_conn')->select();
        $this->client->getSpace('space_conn')->select();

        self::assertSame(3, self::getTotalSelectCalls() - $total);
    }

    public function testFlushSpaces() : void
    {
        $total = self::getTotalSelectCalls();

        $this->client->flushSpaces();
        $this->client->getSpace('space_conn')->select();
        $this->client->flushSpaces();
        $this->client->getSpace('space_conn')->select();

        self::assertSame(4, self::getTotalSelectCalls() - $total);
    }

    public function testSpacesAreFlushedAfterSuccessfulAuthentication() : void
    {
        $this->client->getSpace('space_conn')->select();
        $this->client->authenticate('user_foo', 'foo');

        try {
            $this->client->getSpace('space_conn')->select();
        } catch (RequestFailed $e) {
            // this error means that the client tried to select 'space_conn'
            // from '_vspace' to get the space id instead of getting it directly
            // from the cache (otherwise it will be 'Read access denied' error)
            self::assertSame("Space 'space_conn' does not exist", $e->getMessage());

            return;
        }

        $this->fail();
    }

    public function testSpacesAreNotFlushedAfterFailedAuthentication() : void
    {
        $this->client->getSpace('space_conn')->select();
        $total = self::getTotalSelectCalls();

        try {
            $this->client->authenticate('user_foo', 'incorrect_password');
        } catch (RequestFailed $e) {
            self::assertSame("Incorrect password supplied for user 'user_foo'", $e->getMessage());
        }

        $this->client->getSpace('space_conn')->select();

        self::assertSame(1, self::getTotalSelectCalls() - $total);
    }
}
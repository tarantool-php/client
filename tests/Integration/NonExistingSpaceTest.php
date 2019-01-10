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

use Tarantool\Client\Exception\Exception;

/**
 * @eval create_fixtures()
 */
final class NonExistingSpaceTest extends TestCase
{
    public function testGetByNonExistingName() : void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Space 'non_existing_space' does not exist");

        $this->client->getSpace('non_existing_space');
    }

    public function testGetByNonExistingId() : void
    {
        $space = $this->client->getSpaceById(123456);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Space '123456' does not exist");

        $space->select();
    }
}

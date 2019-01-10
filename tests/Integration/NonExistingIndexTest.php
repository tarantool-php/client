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
final class NonExistingIndexTest extends TestCase
{
    public function testGetByNonExistingName() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp("/No index 'non_existing_index' is defined in space #\d+?/");

        $space->select([1], 'non_existing_index');
    }

    public function testGetByNonExistingId() : void
    {
        $space = $this->client->getSpace('space_misc');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("No index #123456 is defined in space 'space_misc'");

        $space->select([1], 123456);
    }
}

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

namespace Tarantool\Client\Tests\Integration\Requests;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Tarantool\Client\Tests\Integration\TestCase;

final class PingTest extends TestCase
{
    #[DoesNotPerformAssertions]
    public function testPing() : void
    {
        $this->client->ping();
    }
}

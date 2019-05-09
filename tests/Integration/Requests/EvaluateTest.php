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

namespace Tarantool\Client\Tests\Integration\Requests;

use Tarantool\Client\Tests\Integration\TestCase;

final class EvaluateTest extends TestCase
{
    public function testEvaluate() : void
    {
        self::assertSame([42], $this->client->evaluate('return 42'));
    }

    public function testEvaluateWithArgs() : void
    {
        self::assertSame([1, 2, 3], $this->client->evaluate('return ...', 1, 2, 3));
    }
}

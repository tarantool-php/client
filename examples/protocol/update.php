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

use Tarantool\Client\Schema\Operations;

require __DIR__.'/../bootstrap.php';

$client = create_client();
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
    space:insert({1, 10, 'foo'})
    space:insert({2, 20, 'bar'})
    space:insert({3, 30, 'baz'})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);
$result = $space->update([2], Operations::add(1, 5)->andSet(2, 'BAR'));

printf("Result: %s\n", json_encode($result));

/* OUTPUT
Result: [[2,25,"BAR"]]
*/

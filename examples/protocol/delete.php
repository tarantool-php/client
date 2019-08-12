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

require __DIR__.'/../bootstrap.php';

$client = create_client();
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
    space:create_index('secondary', {type = 'tree', parts = {2, 'str'}})
    space:insert({1, 'foo'})
    space:insert({2, 'bar'})
    space:insert({3, 'baz'})
    space:insert({4, 'qux'})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);
$result1 = $space->delete([2]);
$result2 = $space->delete(['baz'], 'secondary');

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));

/* OUTPUT
Result 1: [[2,"bar"]]
Result 2: [[3,"baz"]]
*/

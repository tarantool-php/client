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

use Tarantool\Client\Schema\Criteria;

require __DIR__.'/../bootstrap.php';

$client = create_client();
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
    space:create_index('secondary', {type = 'tree', unique = false, parts = {2, 'str'}})
    space:insert({1, 'foo'})
    space:insert({2, 'bar'})
    space:insert({3, 'bar'})
    space:insert({4, 'bar'})
    space:insert({5, 'baz'})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);
$result1 = $space->select(Criteria::key([1]));
$result2 = $space->select(Criteria::index('secondary')
    ->andKey(['bar'])
    ->andLimit(2)
    ->andOffset(1)
);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));

/* OUTPUT
Result 1: [[1,"foo"]]
Result 2: [[3,"bar"],[4,"bar"]]
*/

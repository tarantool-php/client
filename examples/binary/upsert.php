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
use Tarantool\Client\Schema\Operations;

require __DIR__.'/../bootstrap.php';

$client = create_client();
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
LUA
, $spaceName);

$space = $client->getSpace($spaceName);
$space->upsert([1, 'foo', 'bar'], Operations::set(1, 'baz'));
$space->upsert([1, 'foo', 'bar'], Operations::set(2, 'qux'));

$result = $space->select(Criteria::key([]));
printf("Result: %s\n", json_encode($result));

/* OUTPUT
Result: [[1,"foo","qux"]]
*/

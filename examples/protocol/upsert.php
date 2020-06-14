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

use Tarantool\Client\Schema\Criteria;
use Tarantool\Client\Schema\Operations;

require __DIR__.'/../bootstrap.php';

$client = create_client();
$spaceName = 'example';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    local space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
    space:format({
        {name = 'id', type = 'unsigned'},
        {name = 'name1', type = 'string'},
        {name = 'name2', type = 'string'}
    })
LUA
, $spaceName);

$space = $client->getSpace($spaceName);
$space->upsert([1, 'foo', 'bar'], Operations::set(1, 'baz'));

if (server_version_at_least('2.3', $client)) {
    $space->upsert([1, 'foo', 'bar'], Operations::set('name2', 'qux'));
} else {
    $space->upsert([1, 'foo', 'bar'], Operations::set(2, 'qux'));
}

$result = $space->select(Criteria::key([]));
printf("Result: %s\n", json_encode($result));

/* OUTPUT
Result: [[1,"foo","qux"]]
*/

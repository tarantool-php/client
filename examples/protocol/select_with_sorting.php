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

require __DIR__.'/../bootstrap.php';

$client = create_client();
$spaceName = 'cars';

$client->evaluate(
<<<LUA
    if box.space[...] then box.space[...]:drop() end
    local space = box.schema.space.create(...)
    space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
    space:create_index('price', {type = 'tree', unique = false, parts = {3, 'unsigned'}})
    space:insert({1, 'Aston Martin Lagonda EV', 250})
    space:insert({2, 'Bentley Flying Spur Speed', 215})
    space:insert({3, 'Ferrari SF90 Stradale', 625})
    space:insert({4, 'Maserati MC20', 200})
    space:insert({5, 'Rolls Royce Ghost', 315})
LUA
, $spaceName);

$cars = $client->getSpace($spaceName);

echo "Top 3 most expensive cars:\n";
foreach ($cars->select(Criteria::index('price')
    ->andLtIterator()
    ->andLimit(3)
) as $car) {
    printf("$%sk - %s\n", $car[2], $car[1]);
}

echo "\n";

echo "Top 3 most expensive cars under $500k:\n";
foreach ($cars->select(Criteria::index('price')
    ->andKey([500])
    ->andLtIterator()
    ->andLimit(3)
) as $car) {
    printf("$%sk - %s\n", $car[2], $car[1]);
}

echo "\n";

echo "Top 3 least expensive cars:\n";
foreach ($cars->select(Criteria::index('price')
    ->andGtIterator()
    ->andLimit(3)
) as $car) {
    printf("$%sk - %s\n", $car[2], $car[1]);
}

/* OUTPUT
Top 3 most expensive cars:
$625k - Ferrari SF90 Stradale
$315k - Rolls Royce Ghost
$250k - Aston Martin Lagonda EV

Top 3 most expensive cars under $500k:
$315k - Rolls Royce Ghost
$250k - Aston Martin Lagonda EV
$215k - Bentley Flying Spur Speed

Top 3 least expensive cars:
$200k - Maserati MC20
$215k - Bentley Flying Spur Speed
$250k - Aston Martin Lagonda EV
*/

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

require __DIR__.'/../bootstrap.php';

$client = create_client();
$client->executeUpdate('DROP TABLE IF EXISTS users');

$result1 = $client->executeUpdate('
    CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email VARCHAR(255))
');

$result2 = $client->executeUpdate(
    'INSERT INTO users VALUES (null, :email)',
    [':email' => 'foobar@example.com']
);

$result3 = $client->executeQuery(
    'SELECT * FROM users WHERE email = ?',
    'foobar@example.com'
);

printf("Result 1: %s\n", json_encode($result1->count()));
printf("Result 2: %s\n", json_encode(['cnt' => $result2->count(), 'ids' => $result2->getAutoincrementIds()]));
printf("Result 3: %s\n", json_encode(iterator_to_array($result3)));

/* OUTPUT
Result 1: 1
Result 2: {"cnt":1,"ids":[1]}
Result 3: [{"ID":1,"EMAIL":"foobar@example.com"}]
*/

<?php

/**
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @requires Tarantool 2
 */

declare(strict_types=1);

require __DIR__.'/../bootstrap.php';

$client = create_client();
$client->executeUpdate('DROP TABLE IF EXISTS users');

$result1 = $client->executeUpdate('
    CREATE TABLE users ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "email" VARCHAR(255))
');

$result2 = $client->executeUpdate('
    INSERT INTO users VALUES (null, :email1), (null, :email2)
',
    [':email1' => 'foo@example.com'],
    [':email2' => 'bar@example.com']
);

$result3 = $client->executeQuery('SELECT * FROM users WHERE "email" = ?', 'foo@example.com');
$result4 = $client->executeQuery('SELECT * FROM users WHERE "id" IN (?, ?)', 1, 2);

printf("Result 1: %s\n", json_encode([$result1->count(), $result1->getAutoincrementIds()]));
printf("Result 2: %s\n", json_encode([$result2->count(), $result2->getAutoincrementIds()]));
printf("Result 3: %s\n", json_encode([$result3->count(), $result3->getFirst()]));
printf("Result 4: %s\n", json_encode(iterator_to_array($result4)));

/* OUTPUT
Result 1: [1,null]
Result 2: [2,[1,2]]
Result 3: [1,{"id":1,"email":"foo@example.com"}]
Result 4: [{"id":1,"email":"foo@example.com"},{"id":2,"email":"bar@example.com"}]
*/

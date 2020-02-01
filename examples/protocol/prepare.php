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
ensure_server_version_at_least('2.3.1-68', $client);

$client->execute('DROP TABLE IF EXISTS users');
$client->execute('CREATE TABLE users ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(50))');

$stmt = $client->prepare('INSERT INTO users VALUES(null, ?)');
for ($i = 1; $i <= 100; ++$i) {
    $stmt->execute("name_$i");
    // you can also use executeSelect() and executeUpdate(), e.g.:
    // $lastInsertIds = $stmt->executeUpdate("name_$i")->getAutoincrementIds();
}
$stmt->close();

$result = $client->executeQuery('SELECT COUNT("id") AS "cnt" FROM users');

printf("Result: %s\n", json_encode($result->getFirst()));

/* OUTPUT
Result: {"cnt":100}
*/

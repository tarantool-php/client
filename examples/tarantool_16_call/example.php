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

use App\LegacyCallMiddleware;

$loader = require __DIR__.'/../bootstrap.php';
$loader->addPsr4('App\\', __DIR__);

$client = create_client();
$client17plus = $client->withMiddleware(LegacyCallMiddleware::newResponseMarshalling());
$client16 = $client->withMiddleware(LegacyCallMiddleware::legacyResponseMarshalling());

$result = $client->call('math.min', 5, 3, 8);
$result17plus = $client17plus->call('math.min', 5, 3, 8);
$result16 = $client16->call('math.min', 5, 3, 8);

printf("Actual response: %s\n", json_encode($result));
printf("  1.7+ response: %s\n", json_encode($result17plus));
printf("   1.6 response: %s\n", json_encode($result16));

/* OUTPUT
Actual response: [3]
  1.7+ response: [3]
   1.6 response: [[3]]
*/

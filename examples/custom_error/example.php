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

use App\UserNotFound;
use Tarantool\Client\Middleware\CustomErrorMiddleware;

$loader = require __DIR__.'/../bootstrap.php';
$loader->addPsr4('App\\', __DIR__);

$client = create_client();
ensure_server_version_at_least('2.4.1', $client);
ensure_pure_packer($client);

// CustomErrorMiddleware::fromNamespace('App') will map Lua errors to "App\<lua_error.custom_type>" classes.
// For more options, see the CustomErrorMiddleware class source.
$client = $client->withMiddleware(CustomErrorMiddleware::fromNamespace('App'));

try {
    $client->evaluate('box.error({
        type = "UserNotFound",
        code = 42,
        reason = "User #99 not found",
    })');
} catch (UserNotFound $e) {
    printf("%s: [%d] %s\n", get_class($e), $e->getCode(), $e->getMessage());
}

/* OUTPUT
App\UserNotFound: [42] User #99 not found
*/

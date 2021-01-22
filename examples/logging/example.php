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

use App\SlowRequestHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Tarantool\Client\Middleware\LoggingMiddleware;

$loader = require __DIR__.'/../bootstrap.php';
$loader->addPsr4('App\\', __DIR__);

$stream = new StreamHandler('php://stdout' /*, Logger::WARNING */);

$logger = new Logger('example');
$logger->pushHandler($stream);
$logger->pushHandler(new SlowRequestHandler($stream, 30));

$client = create_client()->withMiddleware(new LoggingMiddleware($logger));

$client->ping();
$client->evaluate('require("fiber").sleep(.1)');

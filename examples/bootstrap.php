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

use MessagePack\Packer;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;

return require __DIR__.'/../vendor/autoload.php';

function create_client() : Client
{
    $connection = isset($_SERVER['argv'][1])
        ? StreamConnection::create($_SERVER['argv'][1])
        : StreamConnection::createTcp();

    $packer = class_exists(Packer::class)
        ? new PurePacker()
        : new PeclPacker();

    return new Client(new DefaultHandler($connection, $packer));
}

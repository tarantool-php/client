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

use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\PeclPacker;
use Tarantool\Client\Packer\PurePacker;

require __DIR__.'/../vendor/autoload.php';

function create_client() : Client
{
    $connection = isset($_SERVER['argv'][1])
        ? StreamConnection::create($_SERVER['argv'][1])
        : StreamConnection::createTcp();

    $packer = class_exists(PurePacker::class)
        ? new PurePacker()
        : new PeclPacker();

    return new Client(new DefaultHandler($connection, $packer));
}

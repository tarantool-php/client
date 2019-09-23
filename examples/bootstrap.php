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

use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\PackerFactory;

return require __DIR__.'/../vendor/autoload.php';

function create_client() : Client
{
    $connection = isset($_SERVER['argv'][1])
        ? StreamConnection::create($_SERVER['argv'][1])
        : StreamConnection::createTcp();

    return new Client(new DefaultHandler($connection, PackerFactory::create()));
}

function get_server_version(Client $client) : int
{
    $connection = $client->getHandler()->getConnection();
    if (!$greeting = $connection->open()) {
        throw new \RuntimeException('Failed to retrieve the server version.');
    }

    return $greeting->getServerVersion();
}

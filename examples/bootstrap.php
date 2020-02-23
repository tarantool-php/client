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
use Tarantool\Client\Packer\PurePacker;
use Tarantool\Client\Tests\Integration\ExamplesTest;

return require __DIR__.'/../vendor/autoload.php';

function create_client() : Client
{
    $connection = isset($_SERVER['argv'][1])
        ? StreamConnection::create($_SERVER['argv'][1])
        : StreamConnection::createTcp();

    return new Client(new DefaultHandler($connection, PackerFactory::create()));
}

function server_version_at_least(string $version, Client $client) : bool
{
    $connection = $client->getHandler()->getConnection();
    if (!$greeting = $connection->open()) {
        throw new \RuntimeException('Failed to retrieve server version');
    }

    return version_compare($greeting->getServerVersion(), $version, '>=');
}

function ensure_server_version_at_least(string $version, Client $client) : void
{
    if (server_version_at_least($version, $client)) {
        return;
    }

    printf('Tarantool version >= %s is required to run "%s"%s', $version, $_SERVER['SCRIPT_FILENAME'], PHP_EOL);
    exit(ExamplesTest::EXIT_CODE_SKIP);
}

function ensure_extension(string $name) : void
{
    if (extension_loaded($name)) {
        return;
    }

    printf('PHP extension "%s" is required to run "%s"%s', $name, $_SERVER['SCRIPT_FILENAME'], PHP_EOL);
    exit(ExamplesTest::EXIT_CODE_SKIP);
}

function ensure_pure_packer(Client $client) : void
{
    $packer = $client->getHandler()->getPacker();
    if ($packer instanceof PurePacker) {
        return;
    }

    printf('Client needs to be configured to use pure packer to run "%s"%s', $_SERVER['SCRIPT_FILENAME'], PHP_EOL);
    exit(ExamplesTest::EXIT_CODE_SKIP);
}

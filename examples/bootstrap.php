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

use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Packer\Packer;
use Tarantool\Client\Packer\PackerFactory;
use Tarantool\Client\Packer\PurePacker;

return require __DIR__.'/../vendor/autoload.php';

function create_client(?Packer $packer = null) : Client
{
    $connection = isset($_SERVER['argv'][1])
        ? StreamConnection::create($_SERVER['argv'][1])
        : StreamConnection::createTcp();

    return new Client(new DefaultHandler($connection, $packer ?? PackerFactory::create()));
}

function server_version_at_least(string $version, Client $client) : bool
{
    [$info] = $client->call('box.info');
    $actualVersion = preg_replace('/-[^-]+$/', '', $info['version']);

    return version_compare($actualVersion, $version, '>=');
}

function ensure_server_version_at_least(string $version, Client $client) : void
{
    if (server_version_at_least($version, $client)) {
        return;
    }

    requirement_exit('Tarantool version >= %s is required to run "%s"', $version, $_SERVER['SCRIPT_FILENAME']);
}

function ensure_extension(string $name) : void
{
    if (extension_loaded($name)) {
        return;
    }

    requirement_exit('PHP extension "%s" is required to run "%s"', $name, $_SERVER['SCRIPT_FILENAME']);
}

function ensure_class(string $className, string $requireMessage = '') : void
{
    if (class_exists($className)) {
        return;
    }

    $errorMessage = $requireMessage ?: sprintf('Class "%s" is required', $className);

    requirement_exit('%s to run "%s"', $errorMessage, $_SERVER['SCRIPT_FILENAME']);
}

function ensure_pure_packer(Client $client) : void
{
    $packer = $client->getHandler()->getPacker();
    if ($packer instanceof PurePacker) {
        return;
    }

    requirement_exit('Client needs to be configured to use pure packer to run "%s"', $_SERVER['SCRIPT_FILENAME']);
}

function requirement_exit(string $message, ...$args) : void
{
    if ($args) {
        $message = sprintf($message, ...$args);
    }

    echo "Unfulfilled requirement:\n$message\n";
    exit(0);
}

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
use Tarantool\Client\Packer\PurePacker;

return require __DIR__.'/../vendor/autoload.php';

function create_client(?Packer $packer = null) : Client
{
    $connection = isset($_SERVER['argv'][1])
        ? StreamConnection::create($_SERVER['argv'][1])
        : StreamConnection::createTcp();

    return new Client(new DefaultHandler($connection, $packer ?? PurePacker::fromAvailableExtensions()));
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

    /** @psalm-suppress PossiblyUndefinedArrayOffset */
    requirement_exit('Tarantool version >= %s is required to run "%s"', $version, $_SERVER['SCRIPT_FILENAME']);
}

function ensure_extension(string $name) : void
{
    if (extension_loaded($name)) {
        return;
    }

    /** @psalm-suppress PossiblyUndefinedArrayOffset */
    requirement_exit('PHP extension "%s" is required to run "%s"', $name, $_SERVER['SCRIPT_FILENAME']);
}

function requirement_exit(string $message, ...$args) : void
{
    echo "Unfulfilled requirement:\n";
    echo $args ? sprintf($message, ...$args) : $message, "\n";
    exit(1);
}

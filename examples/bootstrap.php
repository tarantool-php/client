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

require __DIR__.'/../vendor/autoload.php';

function create_client() : Client
{
    return isset($_SERVER['argv'][1])
        ? Client::fromDsn($_SERVER['argv'][1])
        : Client::fromDefaults()
    ;
}

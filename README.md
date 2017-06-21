# PHP client for Tarantool

[![Build Status](https://travis-ci.org/tarantool-php/client.svg?branch=master)](https://travis-ci.org/tarantool-php/client)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarantool-php/client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/tarantool-php/client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)

This version of client requires Tarantool 1.7.1 or above. 
For older versions of Tarantool please use [v0.2.0](https://github.com/tarantool-php/client/releases/tag/v0.2.0).


## Installation

The recommended way to install the library is through [Composer](http://getcomposer.org):

```sh
$ composer require tarantool/client:@dev
```


## Usage

```php
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Packer\PurePacker;

$conn = new StreamConnection();
// or
// $conn = new StreamConnection('tcp://127.0.0.1:3301');
// $conn = new StreamConnection('tcp://127.0.0.1:3301', ['socket_timeout' => 5.0, 'connect_timeout' => 5.0]);
// $conn = new StreamConnection('unix:///tmp/tarantool_instance.sock');

$client = new Client($conn, new PurePacker());
// or
// $client = new Client($conn, new PeclPacker());

// If authentication credentials required
// $client->authenticate('username', 'userpass');

$space = $client->getSpace('my_space');

// Selecting all data
$result = $space->select();
var_dump($result->getData());

// Result: inserted tuple { 1, 'foo', 'bar' }
$space->insert([1, 'foo', 'bar']);

// Result: inserted tuple { 2, 'baz', 'qux'}
$space->upsert([2, 'baz', 'qux'], [['=', 1, 'BAZ'], ['=', 2, 'QUX']]);

// Result: updated tuple { 2, 'baz', 'qux'} with { 2, 'BAZ', 'QUX' }
$space->upsert([2, 'baz', 'qux'], [['=', 1, 'BAZ'], ['=', 2, 'QUX']]);

$result = $client->evaluate('return ...', [42]);
var_dump($result->getData());

$result = $client->call('box.stat');
var_dump($result->getData());
```

> *Note*
>
> Using packer classes provided by the library require to install additional dependencies,
> which are not bundled with the library directly. Therefore, you have to install them manually.
> For example, if you plan to use PurePacker, install the [rybakit/msgpack](https://github.com/rybakit/msgpack.php#installation) package.
> See the "[suggest](composer.json#L21-L22)" section of composer.json for other alternatives.


## Tests

To run unit tests:

```sh
$ phpunit --testsuite Unit
```

To run integration tests:

```sh
$ phpunit --testsuite Integration
```

> Make sure to start [client.lua](tests/Integration/client.lua) first.

To run all tests:

```sh
$ phpunit
```

If you already have Docker installed, you can run the tests in a docker container.
First, create a container:

```sh
$ ./dockerfile.py | docker build -t client -
```

The command above will create a container named `client` with PHP 7.1 runtime.
You may change the default runtime by defining the `IMAGE` environment variable:

```sh
$ IMAGE='php:7.0-cli' ./dockerfile.py | docker build -t client -
```

> See a list of various images [here](.travis.yml#L9-L26).


Then run Tarantool instance (needed for integration tests):

```sh
$ docker network create tarantool-php
$ docker run -d --net=tarantool-php --name=tarantool -v `pwd`:/client \
    tarantool/tarantool:1.7 tarantool /client/tests/Integration/client.lua
```

And then run both unit and integration tests:

```sh
$ docker run --rm --net=tarantool-php --name client -v $(pwd):/client -w /client client
```


## License

The library is released under the MIT License. See the bundled [LICENSE](LICENSE) file for details.

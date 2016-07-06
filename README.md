# PHP client for Tarantool

[![Build Status](https://travis-ci.org/tarantool-php/client.svg?branch=master)](https://travis-ci.org/tarantool-php/client)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarantool-php/client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/tarantool-php/client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)


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
// $conn = new StreamConnection('tcp://127.0.0.1:3301');
// $conn = new StreamConnection('tcp://127.0.0.1:3301', ['socket_timeout' => 5.0, 'connect_timeout' => 5.0]);
// $conn = new StreamConnection('unix:///tmp/tarantool_instance.sock');

$client = new Client($conn, new PurePacker());
// $client = new Client($conn);
// $client = new Client($conn, new PeclPacker());
// $client = new Client($conn, new PeclLitePacker());

$space = $client->getSpace('my_space');
$result = $space->select();
var_dump($result->getData());

$result = $client->evaluate('return ...', [42]);
var_dump($result->getData());

$result = $client->call('box.stat');
var_dump($result->getData());
```


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

The command above will create a container named `client` with PHP 5.6 runtime.
You may change the default runtime by defining the `IMAGE` environment variable:

```sh
$ IMAGE='php:7.0-cli' ./dockerfile.py | docker build -t client -
```

> See a list of various images [here](.travis.yml#L9-L30).


Then run Tarantool instance (needed for integration tests):

```sh
$ docker run -d --name tarantool -v $(pwd):/client tarantool/tarantool \
    /client/tests/Integration/client.lua
```

And then run both unit and integration tests:

```sh
$ docker run --rm --name client --link tarantool -v $(pwd):/client -w /client client
```


## License

The library is released under the MIT License. See the bundled [LICENSE](LICENSE) file for details.

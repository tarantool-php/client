# PHP client for Tarantool

[![Build Status](https://travis-ci.org/tarantool-php/client.svg?branch=master)](https://travis-ci.org/tarantool-php/client)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarantool-php/client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/tarantool-php/client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)

A pure PHP client for [Tarantool](http://tarantool.io) 1.7.1 or above.


## Features

 * Written in pure PHP, no extensions are required
 * Supports Unix domain sockets
 * Supports SQL protocol
 * Supports user defined types
 * Highly customizable
 * [Tested](https://travis-ci.org/tarantool-php/client) on PHP 7.1-7.4 and Tarantool 1.7-2.2
 * Being used in other open source projects, including [Queue](https://github.com/tarantool-php/queue), [Mapper](https://github.com/tarantool-php/mapper), [Web Admin UI](https://github.com/basis-company/tarantool-admin) and [more](https://github.com/tarantool-php).


## Table of contents

 * [Installation](#installation)
 * [Creating a client](#creating-a-client)
 * [Handlers](#handlers)
 * [Middleware](#middleware)
 * [Data manipulation](#data-manipulation)
   * [Binary protocol](#binary-protocol)
   * [SQL protocol](#sql-protocol)
   * [User defined types](#user-defined-types) 
 * [Tests](#tests)
 * [License](#license)


## Installation

The recommended way to install the library is through [Composer](http://getcomposer.org):

```bash
composer require tarantool/client
```


## Creating a client

The easiest way to create a client is by using the default configuration:

```php
use Tarantool\Client\Client;

$client = Client::fromDefaults();
```

The client will be configured to connect to `127.0.0.1` on port `3301` with the default stream connection options.
A custom configuration can be accomplished by one of several methods listed.

#### DSN string

The client supports the following Data Source Name formats:

```
tcp://[[username[:password]@]host[:port][/?option1=value1&optionN=valueN]
unix://[[username[:password]@]path[/?option1=value1&optionN=valueN]
```

Some examples:

```php
use Tarantool\Client\Client;

$client = Client::fromDsn('tcp://127.0.0.1');
$client = Client::fromDsn('tcp://[fe80::1]:3301');
$client = Client::fromDsn('tcp://user:pass@example.com:3301');
$client = Client::fromDsn('tcp://user@example.com/?connect_timeout=5&max_retries=3');
$client = Client::fromDsn('unix:///var/run/tarantool/my_instance.sock');
$client = Client::fromDsn('unix://user:pass@/var/run/tarantool/my_instance.sock?max_retries=3');
```

If the username, password, path or options include special characters such as `@`, `:`, `/` or `%`,
they must be encoded according to [RFC 3986](https://tools.ietf.org/html/rfc3986#section-2.1)
(for example, with the [rawurlencode()](https://www.php.net/manual/en/function.rawurlencode.php) function).


#### Array of options

It is also possible to create the client from an array of configuration options:

```php
use Tarantool\Client\Client;

$client = Client::fromOptions([
    'uri' => 'tcp://127.0.0.1:3301',
    'username' => '<username>',
    'password' => '<password>',
    ...
);
```

The following options are available:

Name | Type | Default | Description
--- | :---: | :---: | ---
*uri* | string | 'tcp://127.0.0.1:3301' | The connection uri that is used to create a `StreamConnection` object.
*connect_timeout* | integer | 5 | The number of seconds that the client waits for a connect to a Tarantool server before throwing a `ConnectionFailed` exception.
*socket_timeout* | integer | 5 | The number of seconds that the client waits for a respond from a Tarantool server before throwing a `CommunicationFailed` exception.
*tcp_nodelay* | boolean | true | Whether the Nagle algorithm is disabled on TPC connections.
*username* | string | | The username for the user being authenticated.
*password* | string | '' | The password for the user being authenticated. If the username is not set, this option will be ignored.
*max_retries* | integer | 0 | The number of times the client retries unsuccessful request. If set to 0, the client does not try to resend the request after the initial unsuccessful attempt.


#### Custom build

For more deep customisation, you can build a client from the ground up:

```php
use MessagePack\BufferUnpacker;
use MessagePack\Packer;
use Tarantool\Client\Client;
use Tarantool\Client\Connection\StreamConnection;
use Tarantool\Client\Handler\DefaultHandler;
use Tarantool\Client\Handler\MiddlewareHandler;
use Tarantool\Client\Middleware\AuthMiddleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Packer\PurePacker;

$connection = StreamConnection::createTcp('tcp://127.0.0.1:3301', [
    'socket_timeout' => 5,
    'connect_timeout' => 5,
    // ...
]);

$pureMsgpackPacker = new Packer();
$pureMsgpackUnpacker = new BufferUnpacker();
$packer = new PurePacker($pureMsgpackPacker, $pureMsgpackUnpacker);

$handler = new DefaultHandler($connection, $packer);
$handler = MiddlewareHandler::create($handler, [
    new AuthMiddleware('<username>', '<password>'),
    RetryMiddleware::exponential(3),
    // ...
]);

$client = new Client($handler);
```

> *Note*
>
> Using packer classes provided by the library require to install additional dependencies,
> which are not bundled with the library directly. Therefore, you have to install them manually.
> For example, if you plan to use the `PurePacker`, install the [rybakit/msgpack](https://github.com/rybakit/msgpack.php#installation) package.
> See the "[suggest](composer.json#L24)" section of composer.json for other alternatives.


## Handlers

A handler is a function which transforms a request into a response. Once you have created a handler object, 
you can make requests to Tarantool, for example:

```php
use Tarantool\Client\IProto;
use Tarantool\Client\Request\Call;

...

$request = new Call('box.stat');
$response = $handler->handle($request);
$data = $response->getBodyField(IProto::DATA);
```
  
The library ships with two handlers:

 * `DefaultHandler` is used for handling low-level communication with a Tarantool server
 * `MiddlewareHandler` can be used as an extension point for an underlying handler via [middleware](#middleware)


## Middleware

Middleware is the suggested way to extend the client with custom functionality. There are several middleware classes 
implemented to address the common use cases, like authentification, logging and [more](src/Middleware). 
The usage is straightforward:

```php
use Tarantool\Client\Client;
use Tarantool\Client\Middleware\AuthMiddleware;

$client = Client::fromDefaults()->withMiddleware(
    new AuthMiddleware('<username>', '<password>')
);
```

You may also assign multiple middleware to a client (they will be executed in [FIFO](https://en.wikipedia.org/wiki/FIFO_(computing_and_electronics) order):

```php
use Tarantool\Client\Client;
use Tarantool\Client\Middleware\LoggingMiddleware;

$client = Client::fromDefaults()->withMiddleware(
    new LoggingMiddleware(...),
    new MyGuardMiddleware(...),
    new MyMetricsMiddleware(...)
);
```


## Data manipulation

### Binary protocol

The following are examples of binary protocol requests. For more detailed information and examples please see 
the [official documentation](https://www.tarantool.io/en/doc/2.1/book/box/box_space/#box-space-operations-detailed-examples).


<details>
<summary><strong>Select</strong></summary><br />

*Fixtures*

```lua
space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
space:create_index('secondary', {type = 'tree', unique = false, parts = {2, 'str'}})
space:insert({1, 'foo'})
space:insert({2, 'bar'})
space:insert({3, 'bar'})
space:insert({4, 'bar'})
space:insert({5, 'baz'})
```

*Code*

```php
$space = $client->getSpace($spaceName);
$result1 = $space->select(Criteria::key([1]));
$result2 = $space->select(Criteria::index('secondary')
    ->andKey(['bar'])
    ->andLimit(2)
    ->andOffset(1)
);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
```

*Output*

```
Result 1: [[1,"foo"]]
Result 2: [[3,"bar"],[4,"bar"]]
```
</details>


<details>
<summary><strong>Insert</strong></summary><br />

*Fixtures*

```lua
space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
```

*Code*

```php
$space = $client->getSpace('example');
$result = $space->insert([1, 'foo', 'bar']);

printf("Result: %s\n", json_encode($result));
```

*Output*

```
Result: [[1,"foo","bar"]]
```

*Space data*

```
tarantool> box.space.example:select()
---
- - [1, 'foo', 'bar']
...
```
</details>


<details>
<summary><strong>Update</strong></summary><br />

*Fixtures*

```lua
space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
space:insert({1, 10, 'foo'})
space:insert({2, 20, 'bar'})
space:insert({3, 30, 'baz'})
```

*Code*

```php
$space = $client->getSpace('example');
$result = $space->update([2], Operations::add(1, 5)->andSet(2, 'BAR'));

printf("Result: %s\n", json_encode($result));
```

*Output*

```
Result: [[2,25,"BAR"]]
```

*Space data*

```
tarantool> box.space.example:select()
---
- - [1, 10, 'foo']
  - [2, 25, 'BAR']
  - [3, 30, 'baz']
...
```
</details>


<details>
<summary><strong>Upsert</strong></summary><br />

*Fixtures*

```lua
space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
```

*Code*

```php
$space = $client->getSpace('example');
$space->upsert([1, 'foo', 'bar'], Operations::set(1, 'baz'));
$space->upsert([1, 'foo', 'bar'], Operations::set(2, 'qux'));
```

*Space data*

```
tarantool> box.space.example:select()
---
- - [1, 'foo', 'qux']
...
```
</details>


<details>
<summary><strong>Replace</strong></summary><br />

*Fixtures*

```lua
space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
space:insert({1, 'foo'})
space:insert({2, 'bar'})
```

*Code*

```php
$space = $client->getSpace('example');
$result1 = $space->replace([2, 'BAR']);
$result2 = $space->replace([3, 'BAZ']);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
```

*Output*

```
Result 1: [[2,"BAR"]]
Result 2: [[3,"BAZ"]]
```

*Space data*

```
tarantool> box.space.example:select()
---
- - [1, 'foo']
  - [2, 'BAR']
  - [3, 'BAZ']
...
```
</details>


<details>
<summary><strong>Delete</strong></summary><br />

*Fixtures*

```lua
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
space:create_index('secondary', {type = 'tree', parts = {2, 'str'}})
space:insert({1, 'foo'})
space:insert({2, 'bar'})
space:insert({3, 'baz'})
space:insert({4, 'qux'})
```

*Code*

```php
$space = $client->getSpace('example');
$result1 = $space->delete([2]);
$result2 = $space->delete(['baz'], 1);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
```

*Output*

```
Result 1: [[2,"bar"]]
Result 2: [[3,"baz"]]
```

*Space data*

```
tarantool> box.space.example:select()
---
- - [1, 'foo']
  - [4, 'qux']
...
```
</details>


<details>
<summary><strong>Call</strong></summary><br />

*Fixtures*

```lua
function func_42()
    return 42
end
```

*Code*

```php
$result1 = $client->call('func_42');
$result2 = $client->call('math.min', 5, 3, 8);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
```

*Output*

```
Result 1: [42]
Result 2: [3]
```
</details>


<details>
<summary><strong>Evaluate</strong></summary><br />

*Code*

```php
$result1 = $client->evaluate('function func_42() return 42 end');
$result2 = $client->evaluate('return func_42()');
$result3 = $client->evaluate('return math.min(...)', 5, 3, 8);

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
printf("Result 3: %s\n", json_encode($result3));
```

*Output*

```
Result 1: []
Result 2: [42]
Result 3: [3]
```
</details>


### SQL protocol

Below is an example of the SQL execute request. For more detailed information and examples please see 
the [official documentation](https://www.tarantool.io/en/doc/2.1/tutorials/sql_tutorial).

<details>
<summary><strong>Exacute</strong></summary><br />

*Code*

```php
$result1 = $client->executeUpdate('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email VARCHAR(255))');
$result2 = $client->executeUpdate('INSERT INTO users VALUES (null, :email)', [':email' => 'foobar@example.com']);
$result3 = $client->executeQuery('SELECT * FROM users WHERE email = ?', 'foobar@example.com');

printf("Result 1: %s\n", json_encode([$result1->count(), $result1->getAutoincrementIds()]));
printf("Result 2: %s\n", json_encode([$result2->count(), $result2->getAutoincrementIds()]));
printf("Result 3: %s\n", json_encode(iterator_to_array($result3)));
```

*Output*

```
Result 1: [1,null]
Result 2: [1,[1]]
Result 3: [{"ID":1,"EMAIL":"foobar@example.com"}]
```
</details>


### User defined types

To store complex structures inside a tuple you may want to use objects:

```php
$space->insert([42, Money::EUR(500)]);
[[$id, $money]] = $space->select(Ctiteria::key([42]));
```

The [PeclPacker](src/Packer/PeclPacker.php) supports object serialization out of the box, no extra configuration is needed.  
For the [PurePacker](src/Packer/PurePacker.php) you will need to write a type transformer 
that converts your objects to and from MessagePack structures (for more details, read 
the msgpack.php's [README](https://github.com/rybakit/msgpack.php#type-transformers)). 
Once you have implemented your transformer, you should register it with the packer object:

```php
$transformer = new MoneyTransformer();

$packer = new PurePacker(
    (new Packer())->registerTransformer($transformer),
    (new BufferUnpacker())->registerTransformer($transformer)
);

$client = new Client(new DefaultHandler($connection, $packer));
```

> *Note*
>
> A working example of using the user defined types can be found in the [examples](examples/user_defined_type) folder. 



## Tests

To run unit tests:

```bash
vendor/bin/phpunit --testsuite unit
```

To run integration tests:

```bash
vendor/bin/phpunit --testsuite integration
```

> Make sure to start [client.lua](tests/Integration/client.lua) first.

To run all tests:

```bash
vendor/bin/phpunit
```

If you already have Docker installed, you can run the tests in a docker container.
First, create a container:

```bash
./dockerfile.sh | docker build -t client -
```

The command above will create a container named `client` with PHP 7.3 runtime.
You may change the default runtime by defining the `PHP_IMAGE` environment variable:

```bash
PHP_IMAGE='php:7.2-cli' ./dockerfile.sh | docker build -t client -
```

> See a list of various images [here](.travis.yml#L12).


Then run a Tarantool instance (needed for integration tests):

```bash
docker network create tarantool-php
docker run -d --net=tarantool-php -p 3301:3301 --name=tarantool -v $(pwd)/tests/Integration/client.lua:/client.lua \
    tarantool/tarantool:2 tarantool /client.lua
```

And then run both unit and integration tests:

```bash
docker run --rm --net=tarantool-php -v $(pwd):/client -w /client client
```


## License

The library is released under the MIT License. See the bundled [LICENSE](LICENSE) file for details.

# PHP client for Tarantool

[![Quality Assurance](https://github.com/tarantool-php/client/workflows/QA/badge.svg)](https://github.com/tarantool-php/client/actions?query=workflow%3AQA)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/tarantool-php/client/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/tarantool-php/client/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/tarantool-php/client/?branch=master)
[![Telegram](https://img.shields.io/badge/Telegram-join%20chat-blue.svg)](https://t.me/tarantool_php)

A pure PHP client for [Tarantool](https://www.tarantool.io/en/developers/) 1.7.1 or above.


## Features

 * Written in pure PHP, no extensions are required
 * Supports Unix domain sockets
 * Supports SQL protocol
 * Supports user-defined types (decimals and UUIDs are included)
 * Highly customizable
 * [Thoroughly tested](https://github.com/tarantool-php/client/actions?query=workflow%3AQA)
 * Being used in a number of projects, including [Queue](https://github.com/tarantool-php/queue), 
   [Mapper](https://github.com/tarantool-php/mapper), [Web Admin](https://github.com/basis-company/tarantool-admin) 
   and [others](https://github.com/tarantool-php).


## Table of contents

 * [Installation](#installation)
 * [Creating a client](#creating-a-client)
 * [Handlers](#handlers)
 * [Middleware](#middleware)
 * [Data manipulation](#data-manipulation)
   * [Binary protocol](#binary-protocol)
   * [SQL protocol](#sql-protocol)
   * [User-defined types](#user-defined-types) 
 * [Tests](#tests)
 * [Benchmarks](#benchmarks)
 * [License](#license)


## Installation

The recommended way to install the library is through [Composer](http://getcomposer.org):

```bash
composer require tarantool/client
```

In order to use the [Decimal](https://www.tarantool.io/en/doc/latest/dev_guide/internals/msgpack_extensions/#the-decimal-type) 
type that was added in Tarantool 2.3, you additionally need to install the [decimal](http://php-decimal.io/#installation) 
extension. Also, to improve performance when working with the [UUID](https://www.tarantool.io/en/doc/latest/dev_guide/internals/msgpack_extensions/#the-uuid-type) 
type, which is available since Tarantool 2.4, it is recommended to additionally install the [uuid](https://pecl.php.net/package/uuid) extension. 


## Creating a client

The easiest way to create a client is by using the default configuration:

```php
use Tarantool\Client\Client;

$client = Client::fromDefaults();
```

The client will be configured to connect to `127.0.0.1` on port `3301` with the default stream connection options.
Also, the best available msgpack package will be chosen automatically. A custom configuration can be accomplished
by one of several methods listed.

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
$client = Client::fromDsn('tcp://user@example.com/?connect_timeout=5.0&max_retries=3');
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
*connect_timeout* | float | 5.0 | The number of seconds that the client waits for a connect to a Tarantool server before throwing a `ConnectionFailed` exception.
*socket_timeout* | float | 5.0 | The number of seconds that the client waits for a respond from a Tarantool server before throwing a `CommunicationFailed` exception.
*tcp_nodelay* | boolean | true | Whether the Nagle algorithm is disabled on a TCP connection.
*persistent* | boolean | false | Whether to use a persistent connection.
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
use Tarantool\Client\Middleware\AuthenticationMiddleware;
use Tarantool\Client\Middleware\RetryMiddleware;
use Tarantool\Client\Packer\PurePacker;

$connection = StreamConnection::createTcp('tcp://127.0.0.1:3301', [
    'socket_timeout' => 5.0,
    'connect_timeout' => 5.0,
    // ...
]);

$pureMsgpackPacker = new Packer();
$pureMsgpackUnpacker = new BufferUnpacker();
$packer = new PurePacker($pureMsgpackPacker, $pureMsgpackUnpacker);

$handler = new DefaultHandler($connection, $packer);
$handler = MiddlewareHandler::append($handler, [
    RetryMiddleware::exponential(3),
    new AuthenticationMiddleware('<username>', '<password>'),
    // ...
]);

$client = new Client($handler);
```


## Handlers

A handler is a function which transforms a request into a response. Once you have created a handler object,
you can make requests to Tarantool, for example:

```php
use Tarantool\Client\Keys;
use Tarantool\Client\Request\CallRequest;

...

$request = new CallRequest('box.stat');
$response = $handler->handle($request);
$data = $response->getBodyField(Keys::DATA);
```

The library ships with two handlers:

 * `DefaultHandler` is used for handling low-level communication with a Tarantool server
 * `MiddlewareHandler` is used as an extension point for an underlying handler via [middleware](#middleware)


## Middleware

Middleware is the suggested way to extend the client with custom functionality. There are several middleware classes
implemented to address the common use cases, like authentification, logging and [more](src/Middleware).
The usage is straightforward:

```php
use Tarantool\Client\Client;
use Tarantool\Client\Middleware\AuthenticationMiddleware;

$client = Client::fromDefaults()->withMiddleware(
    new AuthenticationMiddleware('<username>', '<password>')
);
```

You may also assign multiple middleware to the client (they will be executed in [FIFO](https://en.wikipedia.org/wiki/FIFO_(computing_and_electronics)) order):

```php
use Tarantool\Client\Client;
use Tarantool\Client\Middleware\FirewallMiddleware;
use Tarantool\Client\Middleware\LoggingMiddleware;
use Tarantool\Client\Middleware\RetryMiddleware;

...

$client = Client::fromDefaults()->withMiddleware(
    FirewallMiddleware::allowReadOnly(),
    RetryMiddleware::linear(),
    new LoggingMiddleware($logger)
);
```

Please be aware that the order in which you add the middleware does matter. The same middleware,
placed in different order, can give very different or sometimes unexpected behavior.
To illustrate, consider the following configurations:

```php
$client1 = Client::fromDefaults()->withMiddleware(
    RetryMiddleware::linear(),
    new AuthenticationMiddleware('<username>', '<password>') 
);

$client2 = Client::fromDefaults()->withMiddleware(
    new AuthenticationMiddleware('<username>', '<password>'), 
    RetryMiddleware::linear()
);

$client3 = Client::fromOptions([
    'username' => '<username>',
    'password' => '<password>',
])->withMiddleware(RetryMiddleware::linear());
```

In this example, `$client1` will retry an unsuccessful operation and in case of connection
problems may initiate reconnection with follow-up re-authentication. However, `$client2`
and `$client3` will perform reconnection *without* doing any re-authentication.

> *You may wonder why `$client3` behaves like `$client2` in this case. This is because
> specifying some options (via array or DSN string) may implicitly register middleware.
> Thus, the `username/password` options will be turned into `AuthenticationMiddleware`
> under the hood, making the two configurations identical.*

To make sure your middleware runs first, use the `withPrependedMiddleware()` method:

```php
$client = $client->withPrependedMiddleware($myMiddleware);
```


## Data manipulation

### Binary protocol

The following are examples of binary protocol requests. For more detailed information and examples please see
the [official documentation](https://www.tarantool.io/en/doc/latest/book/box/data_model/#operations).

<details>
<summary><strong>Select</strong></summary><br />

*Fixtures*

```lua
local space = box.schema.space.create('example')
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
$space = $client->getSpace('example');
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
local space = box.schema.space.create('example')
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

```lua
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
local space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}})
space:format({
    {name = 'id', type = 'unsigned'}, 
    {name = 'num', type = 'unsigned'}, 
    {name = 'name', type = 'string'}
})

space:insert({1, 10, 'foo'})
space:insert({2, 20, 'bar'})
space:insert({3, 30, 'baz'})
```

*Code*

```php
$space = $client->getSpace('example');
$result = $space->update([2], Operations::add(1, 5)->andSet(2, 'BAR'));

// Since Tarantool 2.3 you can refer to tuple fields by name:
// $result = $space->update([2], Operations::add('num', 5)->andSet('name', 'BAR'));

printf("Result: %s\n", json_encode($result));
```

*Output*

```
Result: [[2,25,"BAR"]]
```

*Space data*

```lua
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
local space = box.schema.space.create('example')
space:create_index('primary', {type = 'tree', parts = {1, 'unsigned'}}) 
space:format({
    {name = 'id', type = 'unsigned'}, 
    {name = 'name1', type = 'string'}, 
    {name = 'name2', type = 'string'}
})
```

*Code*

```php
$space = $client->getSpace('example');
$space->upsert([1, 'foo', 'bar'], Operations::set(1, 'baz'));
$space->upsert([1, 'foo', 'bar'], Operations::set(2, 'qux'));

// Since Tarantool 2.3 you can refer to tuple fields by name:
// $space->upsert([1, 'foo', 'bar'], Operations::set('name1', 'baz'));
// $space->upsert([1, 'foo', 'bar'], Operations::set('name2'', 'qux'));
```

*Space data*

```lua
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
local space = box.schema.space.create('example')
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

```lua
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
local space = box.schema.space.create('example')
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
$result2 = $space->delete(['baz'], 'secondary');

printf("Result 1: %s\n", json_encode($result1));
printf("Result 2: %s\n", json_encode($result2));
```

*Output*

```
Result 1: [[2,"bar"]]
Result 2: [[3,"baz"]]
```

*Space data*

```lua
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

The following are examples of SQL protocol requests. For more detailed information and examples please see
the [official documentation](https://www.tarantool.io/en/doc/latest/reference/reference_sql/sql/). 
*Note that SQL is supported only as of Tarantool 2.0.*

<details>
<summary><strong>Execute</strong></summary><br />

*Code*

```php
$result1 = $client->executeUpdate('
    CREATE TABLE users ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "email" VARCHAR(255))
');

$result2 = $client->executeUpdate('
    INSERT INTO users VALUES (null, :email1), (null, :email2)
',
    [':email1' => 'foo@example.com'],
    [':email2' => 'bar@example.com']
);

$result3 = $client->executeQuery('SELECT * FROM users WHERE "email" = ?', 'foo@example.com');
$result4 = $client->executeQuery('SELECT * FROM users WHERE "id" IN (?, ?)', 1, 2);

printf("Result 1: %s\n", json_encode([$result1->count(), $result1->getAutoincrementIds()]));
printf("Result 2: %s\n", json_encode([$result2->count(), $result2->getAutoincrementIds()]));
printf("Result 3: %s\n", json_encode([$result3->count(), $result3[0]]));
printf("Result 4: %s\n", json_encode(iterator_to_array($result4)));
```

*Output*

```
Result 1: [1,[]]
Result 2: [2,[1,2]]
Result 3: [1,{"id":1,"email":"foo@example.com"}]
Result 4: [{"id":1,"email":"foo@example.com"},{"id":2,"email":"bar@example.com"}]
```

If you need to execute a dynamic SQL statement whose type you don't know, you can use the generic method `execute()`. 
This method returns a Response object with the body containing either an array of result set rows or an array
with information about the changed rows:

```php
$response = $client->execute('<any-type-of-sql-statement>');
$resultSet = $response->tryGetBodyField(Keys::DATA);

if ($resultSet === null) {
    $sqlInfo = $response->getBodyField(Keys::SQL_INFO);
    $affectedCount = $sqlInfo[Keys::SQL_INFO_ROW_COUNT];
} 
``` 
</details>


<details>
<summary><strong>Prepare</strong></summary><br />

*Note that the `prepare` request is supported only as of Tarantool 2.3.2.*

*Code*

```php
$client->execute('CREATE TABLE users ("id" INTEGER PRIMARY KEY AUTOINCREMENT, "name" VARCHAR(50))');

$stmt = $client->prepare('INSERT INTO users VALUES(null, ?)');
for ($i = 1; $i <= 100; ++$i) {
    $stmt->execute("name_$i");
    // You can also use executeSelect() and executeUpdate(), e.g.:
    // $lastInsertIds = $stmt->executeUpdate("name_$i")->getAutoincrementIds();
}
$stmt->close();

$result = $client->executeQuery('SELECT COUNT("id") AS "cnt" FROM users');

printf("Result: %s\n", json_encode($result[0]));
```

*Output*

```
Result: {"cnt":100}
```
</details>


### User-defined types

To store complex structures inside a tuple you may want to use objects:

```php
$space->insert([42, Money::EUR(500)]);
[[$id, $money]] = $space->select(Criteria::key([42]));
```

This can be achieved by extending the MessagePack type system with your own types. To do this, you need to write 
a MessagePack extension that converts your objects into MessagePack structures and back (for more details, read 
the msgpack.php's [README](https://github.com/rybakit/msgpack.php#custom-types)). Once you have implemented 
your extension, you should register it with the packer object:

```php
$packer = PurePacker::fromExtensions(new MoneyExtension());
$client = new Client(new DefaultHandler($connection, $packer));
```

> *A working example of using the user-defined types can be found in the [examples](examples/user_defined_type) folder.*


## Tests

To run unit tests:

```bash
vendor/bin/phpunit --testsuite unit
```

To run integration tests:

```bash
vendor/bin/phpunit --testsuite integration
```

> *Make sure to start [client.lua](tests/Integration/client.lua) first.*

To run all tests:

```bash
vendor/bin/phpunit
```

If you already have Docker installed, you can run the tests in a docker container.
First, create a container:

```bash
./dockerfile.sh | docker build -t client -
```

The command above will create a container named `client` with PHP 7.4 runtime.
You may change the default runtime by defining the `PHP_IMAGE` environment variable:

```bash
PHP_IMAGE='php:8.0-cli' ./dockerfile.sh | docker build -t client -
```

> *See a list of various images [here](https://hub.docker.com/_/php).*


Then run a Tarantool instance (needed for integration tests):

```bash
docker network create tarantool-php
docker run -d --net=tarantool-php -p 3301:3301 --name=tarantool \
    -v $(pwd)/tests/Integration/client.lua:/client.lua \
    tarantool/tarantool:2 tarantool /client.lua
```

And then run both unit and integration tests:

```bash
docker run --rm --net=tarantool-php -v $(pwd):/client -w /client client
```


## Benchmarks

The benchmarks can be found in the [dedicated repository](https://github.com/tarantool-php/benchmarks).


## License

The library is released under the MIT License. See the bundled [LICENSE](LICENSE) file for details.

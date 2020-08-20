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

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Error;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Response;

final class CustomErrorMiddleware implements Middleware
{
    /** @var \Closure(Error, RequestFailed) : \Exception */
    private $factory;

    /**
     * @param \Closure(Error, RequestFailed) : \Exception $factory
     */
    private function __construct($factory)
    {
        $this->factory = $factory;
    }

    /**
     * Creates the middleware from a provided closure which receives
     * `Tarantool\Client\Error` and `Tarantool\Client\Exception\RequestFailed`
     * objects as arguments and should return a custom exception.
     *
     * Example:
     *
     * ```php
     * $middleware = CustomErrorMiddleware::fromFactory(
     *     static function (Error $err, RequestFailed $ex) : \Exception {
     *         return 'UserNotFound' === $err->tryGetField('custom_type')
     *             ? new UserNotFound($err->getMessage(), $err->getCode())
     *             : $ex;
     *     }
     * );
     * ```
     */
    public static function fromFactory(\Closure $factory) : self
    {
        return new self(
            static function (Error $err, RequestFailed $ex) use ($factory) : \Exception {
                return $factory($err, $ex);
            }
        );
    }

    /**
     * Creates the middleware from an array in which the keys are custom types
     * of box.error objects and the corresponding values are fully qualified names
     * of custom exception classes.
     *
     * Example:
     *
     * ```php
     * $middleware = CustomErrorMiddleware::fromMapping([
     *     'UserNotFound' => UserNotFound::class,
     *     'MyCustomType' => MyCustomException::class,
     *     ...
     * ]);
     * ```
     *
     * @param array<string, class-string<\Exception>> $mapping
     */
    public static function fromMapping(array $mapping) : self
    {
        return new self(
            static function (Error $err, RequestFailed $ex) use ($mapping) : \Exception {
                do {
                    $customType = $err->tryGetField('custom_type');
                    if ($customType && isset($mapping[$customType])) {
                        /** @psalm-suppress UnsafeInstantiation */
                        return new $mapping[$customType]($err->getMessage(), $err->getCode());
                    }
                } while ($err = $err->getPrevious());

                return $ex;
            }
        );
    }

    /**
     * Creates the middleware from the base namespace for the custom exception classes.
     * The exception class name then will be in the format of "<namespace>\<lua_error.custom_type>".
     *
     * Example:
     *
     * ```php
     * $middleware = CustomErrorMiddleware::fromNamespace('Foo\Bar');
     * ```
     */
    public static function fromNamespace(string $namespace) : self
    {
        $namespace = \rtrim($namespace, '\\').'\\';

        return new self(
            static function (Error $err, RequestFailed $ex) use ($namespace) : \Exception {
                if (!$customType = $err->tryGetField('custom_type')) {
                    return $ex;
                }

                /** @var class-string<\Exception> $className */
                $className = $namespace.$customType;

                /** @psalm-suppress UnsafeInstantiation */
                return \class_exists($className)
                    ? new $className($err->getMessage(), $err->getCode())
                    : $ex;
            }
        );
    }

    public function process(Request $request, Handler $handler) : Response
    {
        try {
            return $handler->handle($request);
        } catch (RequestFailed $e) {
            if ($error = $e->getError()) {
                throw ($this->factory)($error, $e);
            }

            throw $e;
        }
    }
}

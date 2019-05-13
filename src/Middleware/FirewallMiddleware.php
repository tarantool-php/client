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

namespace Tarantool\Client\Middleware;

use Tarantool\Client\Exception\RequestForbidden;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\AuthenticateRequest;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Request\Request;
use Tarantool\Client\Request\SelectRequest;
use Tarantool\Client\Response;

final class FirewallMiddleware implements Middleware
{
    private $whitelist;
    private $blacklist;

    public function __construct(array $whitelist, array $blacklist)
    {
        $this->whitelist = self::add($whitelist);
        $this->blacklist = self::add($blacklist);
    }

    public static function whitelist(string ...$requestClasses) : self
    {
        return new self($requestClasses, []);
    }

    public static function blacklist(string ...$requestClasses) : self
    {
        return new self([], $requestClasses);
    }

    public static function readOnly() : self
    {
        return self::whitelist(
            AuthenticateRequest::class,
            PingRequest::class,
            SelectRequest::class
        );
    }

    public function withWhitelist(string ...$requestClasses) : self
    {
        $new = clone $this;
        $new->whitelist = self::add($requestClasses, $new->whitelist);

        return $new;
    }

    public function withBlacklist(string ...$requestClasses) : self
    {
        $new = clone $this;
        $new->blacklist = self::add($requestClasses, $new->blacklist);

        return $new;
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $requestClass = \get_class($request);

        if (isset($this->blacklist[$requestClass])) {
            throw RequestForbidden::fromClass($requestClass);
        }

        if ([] !== $this->whitelist && !isset($this->whitelist[$requestClass])) {
            throw RequestForbidden::fromClass($requestClass);
        }

        return $handler->handle($request);
    }

    private static function add(array $requestClasses, array $list = []) : array
    {
        foreach ($requestClasses as $requestClass) {
            if (!\is_subclass_of($requestClass, Request::class)) {
                throw new \TypeError(\sprintf('Class "%s" should implement %s.', $requestClass, Request::class));
            }
            $list[$requestClass] = true;
        }

        return $list;
    }
}

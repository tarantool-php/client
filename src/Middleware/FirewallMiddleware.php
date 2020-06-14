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

use Tarantool\Client\Exception\RequestDenied;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Request\Request;
use Tarantool\Client\RequestTypes;
use Tarantool\Client\Response;

final class FirewallMiddleware implements Middleware
{
    /** @var array<int, true> */
    private $allowed;

    /** @var array<int, true> */
    private $denied;

    /**
     * @param array $allowed
     * @param array $denied
     */
    private function __construct($allowed, $denied)
    {
        $this->allowed = $allowed ? \array_fill_keys($allowed, true) : [];
        $this->denied = $denied ? \array_fill_keys($denied, true) : [];
    }

    public static function allow(int $requestType, int ...$requestTypes) : self
    {
        return new self([-1 => $requestType] + $requestTypes, []);
    }

    public static function deny(int $requestType, int ...$requestTypes) : self
    {
        return new self([], [-1 => $requestType] + $requestTypes);
    }

    public static function allowReadOnly() : self
    {
        $self = new self([], []);
        $self->allowed = [
            RequestTypes::AUTHENTICATE => true,
            RequestTypes::PING => true,
            RequestTypes::SELECT => true,
        ];

        return $self;
    }

    public function andAllow(int $requestType, int ...$requestTypes) : self
    {
        $new = clone $this;
        $new->allowed += $requestTypes
            ? \array_fill_keys([-1 => $requestType] + $requestTypes, true)
            : [$requestType => true];

        return $new;
    }

    public function andAllowOnly(int $requestType, int ...$requestTypes) : self
    {
        $new = clone $this;
        $new->allowed = $requestTypes
            ? \array_fill_keys([-1 => $requestType] + $requestTypes, true)
            : [$requestType => true];

        return $new;
    }

    public function andDeny(int $requestType, int ...$requestTypes) : self
    {
        $new = clone $this;
        $new->denied += $requestTypes
            ? \array_fill_keys([-1 => $requestType] + $requestTypes, true)
            : [$requestType => true];

        return $new;
    }

    public function andDenyOnly(int $requestType, int ...$requestTypes) : self
    {
        $new = clone $this;
        $new->denied = $requestTypes
            ? \array_fill_keys([-1 => $requestType] + $requestTypes, true)
            : [$requestType => true];

        return $new;
    }

    public function process(Request $request, Handler $handler) : Response
    {
        $requestType = $request->getType();

        if (isset($this->denied[$requestType])) {
            throw RequestDenied::fromObject($request);
        }

        if ([] !== $this->allowed && !isset($this->allowed[$requestType])) {
            throw RequestDenied::fromObject($request);
        }

        return $handler->handle($request);
    }
}

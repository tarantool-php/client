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

namespace App;

use Tarantool\Client\Request\CallRequest;
use Tarantool\Client\Request\Request;

final class LegacyCallRequest implements Request
{
    private const TYPE = 6;

    /** @var non-empty-array<int, string|array> */
    private $body;

    /**
     * @param non-empty-array<int, string|array> $body
     */
    private function __construct($body)
    {
        $this->body = $body;
    }

    public static function fromCallRequest(CallRequest $request) : self
    {
        return new self($request->getBody());
    }

    public function getType() : int
    {
        return self::TYPE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

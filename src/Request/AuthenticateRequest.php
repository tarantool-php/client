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

namespace Tarantool\Client\Request;

use Tarantool\Client\Keys;
use Tarantool\Client\RequestTypes;

final class AuthenticateRequest implements Request
{
    /** @var non-empty-array<int, string|array> */
    private $body;

    public function __construct(string $salt, string $username, string $password = '')
    {
        $hash1 = \sha1($password, true);
        $hash2 = \sha1($hash1, true);

        $this->body = [
            Keys::TUPLE => ['chap-sha1', $hash1 ^ \sha1($salt.$hash2, true)],
            Keys::USER_NAME => $username,
        ];
    }

    public function getType() : int
    {
        return RequestTypes::AUTHENTICATE;
    }

    public function getBody() : array
    {
        return $this->body;
    }
}

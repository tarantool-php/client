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

namespace Tarantool\Client\Request;

use Tarantool\Client\Keys;
use Tarantool\Client\RequestTypes;

final class AuthenticateRequest implements Request
{
    private $salt;
    private $username;
    private $password;
    private $scramble;

    public function __construct(string $salt, string $username, string $password = '')
    {
        $this->salt = $salt;
        $this->username = $username;
        $this->password = $password;
    }

    public function getType() : int
    {
        return RequestTypes::AUTHENTICATE;
    }

    public function getBody() : array
    {
        if (null === $this->scramble) {
            $hash1 = \sha1($this->password, true);
            $hash2 = \sha1($hash1, true);
            $this->scramble = $hash1 ^ \sha1($this->salt.$hash2, true);
        }

        return [
            Keys::TUPLE => ['chap-sha1', $this->scramble],
            Keys::USER_NAME => $this->username,
        ];
    }
}

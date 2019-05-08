<?php

declare(strict_types=1);

/*
 * This file is part of the Tarantool Client package.
 *
 * (c) Eugene Leonovich <gen.work@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;
use Tarantool\Client\RequestTypes;

final class Authenticate implements Request
{
    private $salt;
    private $username;
    private $password;

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
        $hash1 = \sha1($this->password, true);
        $hash2 = \sha1($hash1, true);
        $scramble = \sha1($this->salt.$hash2, true);
        $scramble = self::strxor($hash1, $scramble);

        return [
            IProto::TUPLE => ['chap-sha1', $scramble],
            IProto::USER_NAME => $this->username,
        ];
    }

    private static function strxor(string $rhs, string $lhs) : string
    {
        $result = '';

        for ($i = 0; $i < 20; ++$i) {
            $result .= $rhs[$i] ^ $lhs[$i];
        }

        return $result;
    }
}

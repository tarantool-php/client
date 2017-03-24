<?php

namespace Tarantool\Client\Request;

use Tarantool\Client\IProto;

class AuthenticateRequest implements Request
{
    private $salt;
    private $username;
    private $password;

    public function __construct($salt, $username, $password)
    {
        $this->salt = $salt;
        $this->username = $username;
        $this->password = $password;
    }

    public function getType()
    {
        return self::TYPE_AUTHENTICATE;
    }

    public function getBody()
    {
        $hash1 = sha1($this->password, true);
        $hash2 = sha1($hash1, true);
        $scramble = sha1($this->salt.$hash2, true);
        $scramble = self::strxor($hash1, $scramble);

        return [
            IProto::TUPLE => ['chap-sha1', $scramble],
            IProto::USER_NAME => $this->username,
        ];
    }

    private static function strxor($rhs, $lhs)
    {
        $result = '';

        for ($i = 0; $i < 20; $i++) {
            $result .= $rhs[$i] ^ $lhs[$i];
        }

        return $result;
    }
}

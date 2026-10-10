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

namespace Tarantool\Client\Exception;

use Tarantool\Client\Error;
use Tarantool\Client\Keys;
use Tarantool\Client\Response;

final class RequestFailed extends \RuntimeException implements ClientException
{
    private ?Error $error = null;

    public function getError() : ?Error
    {
        return $this->error;
    }

    public static function fromErrorResponse(Response $response) : self
    {
        $error = null;
        if ($errorMap = $response->tryGetBodyField(Keys::ERROR)) {
            $error = Error::fromMap($errorMap);
        }

        $message = $response->tryGetBodyField(Keys::ERROR_24);
        if ($response->hasBodyField(Keys::ERROR_24) && !\is_string($message)) {
            throw new UnexpectedResponse(\sprintf('Invalid response body field 0x%x', Keys::ERROR_24));
        }

        $message ??= $error?->getMessage();
        if (null === $message) {
            throw new UnexpectedResponse(\sprintf('Missing response body field 0x%x', Keys::ERROR_24));
        }

        $self = new self($message, $response->getCode() & (Response::TYPE_ERROR - 1));

        $self->error = $error;

        return $self;
    }

    public static function unknownSpace(string $spaceName) : self
    {
        return new self(\sprintf("Space '%s' does not exist", $spaceName));
    }

    public static function unknownIndex(string $indexName, int $spaceId) : self
    {
        return new self(\sprintf("No index '%s' is defined in space #%d", $indexName, $spaceId));
    }
}

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
    /** @var Error|null */
    private $error;

    public function getError() : ?Error
    {
        return $this->error;
    }

    public static function fromErrorResponse(Response $response) : self
    {
        $self = new self(
            $response->getBodyField(Keys::ERROR_24),
            $response->getCode() & (Response::TYPE_ERROR - 1)
        );

        if ($error = $response->tryGetBodyField(Keys::ERROR)) {
            $self->error = Error::fromMap($error);
        }

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

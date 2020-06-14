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

namespace Tarantool\Client;

final class Error
{
    private $type;
    private $file;
    private $line;
    private $message;
    private $number;
    private $code;
    private $fields;
    private $previous;

    public function __construct(
        string $type,
        string $file,
        int $line,
        string $message,
        int $number,
        int $code,
        array $fields = [],
        ?self $previous = null
    ) {
        $this->type = $type;
        $this->file = $file;
        $this->line = $line;
        $this->message = $message;
        $this->number = $number;
        $this->code = $code;
        $this->fields = $fields;
        $this->previous = $previous;
    }

    public static function fromMap(array $errorMap) : self
    {
        if (empty($errorMap[Keys::ERROR_STACK])) {
            throw new \InvalidArgumentException('The error map should contain a non-empty error stack');
        }

        $errorStack = $errorMap[Keys::ERROR_STACK];

        $first = \count($errorStack) - 1;
        $error = new self(
            $errorStack[$first][Keys::ERROR_TYPE],
            $errorStack[$first][Keys::ERROR_FILE],
            $errorStack[$first][Keys::ERROR_LINE],
            $errorStack[$first][Keys::ERROR_MESSAGE],
            $errorStack[$first][Keys::ERROR_NUMBER],
            $errorStack[$first][Keys::ERROR_CODE],
            $errorStack[$first][Keys::ERROR_FIELDS] ?? []
        );

        if (0 === $first) {
            return $error;
        }

        for ($i = $first - 1; $i >= 0; --$i) {
            $error = new self(
                $errorStack[$i][Keys::ERROR_TYPE],
                $errorStack[$i][Keys::ERROR_FILE],
                $errorStack[$i][Keys::ERROR_LINE],
                $errorStack[$i][Keys::ERROR_MESSAGE],
                $errorStack[$i][Keys::ERROR_NUMBER],
                $errorStack[$i][Keys::ERROR_CODE],
                $errorStack[$i][Keys::ERROR_FIELDS] ?? [],
                $error
            );
        }

        return $error;
    }

    public function toMap() : array
    {
        $error = $this;
        $errorStack = [];

        do {
            $errorStack[] = [
                Keys::ERROR_TYPE => $error->type,
                Keys::ERROR_FILE => $error->file,
                Keys::ERROR_LINE => $error->line,
                Keys::ERROR_MESSAGE => $error->message,
                Keys::ERROR_NUMBER => $error->number,
                Keys::ERROR_CODE => $error->code,
                Keys::ERROR_FIELDS => $error->fields,
            ];
        } while ($error = $error->getPrevious());

        return [Keys::ERROR_STACK => $errorStack];
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getFile() : string
    {
        return $this->file;
    }

    public function getLine() : int
    {
        return $this->line;
    }

    public function getMessage() : string
    {
        return $this->message;
    }

    public function getNumber() : int
    {
        return $this->number;
    }

    public function getCode() : int
    {
        return $this->code;
    }

    public function getFields() : array
    {
        return $this->fields;
    }

    /**
     * @return mixed
     */
    public function getField(string $name)
    {
        if (\array_key_exists($name, $this->fields)) {
            return $this->fields[$name];
        }

        throw new \OutOfRangeException(\sprintf('The field "%s" does not exist', $name));
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function tryGetField(string $name, $default = null)
    {
        return \array_key_exists($name, $this->fields) ? $this->fields[$name] : $default;
    }

    public function getPrevious() : ?self
    {
        return $this->previous;
    }
}

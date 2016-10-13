<?php

namespace Tarantool\Client;

use Tarantool\Client\Packer\PackUtils;

class ResponseParser
{
    private $buffer = '';
    private $length;

    public function pushIncoming($dataChunk)
    {
        $this->buffer .= $dataChunk;

        return $this->tryParsing();
    }

    private function tryParsing()
    {
        $responses = [];

        do {
            $response = $this->readResponse();
            if (null === $response) {
                break;
            }

            $responses[] = $response;
        } while ('' !== $this->buffer);

        return $responses;
    }

    private function readResponse()
    {
        if (null === $this->length) {
            if (strlen($this->buffer) < IProto::LENGTH_SIZE) {
                return;
            }

            $this->length = PackUtils::unpackLength(substr($this->buffer, 0, IProto::LENGTH_SIZE));
            $this->buffer = (string) substr($this->buffer, IProto::LENGTH_SIZE);
        }

        if (strlen($this->buffer) >= $this->length) {
            $response = substr($this->buffer, 0, $this->length);

            $this->buffer = (string) substr($this->buffer, $this->length);
            $this->length = null;

            return $response;
        }
    }
}

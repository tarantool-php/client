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

namespace Tarantool\Client\Tests\Unit\Middleware;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tarantool\Client\Error;
use Tarantool\Client\Exception\RequestDenied;
use Tarantool\Client\Exception\RequestFailed;
use Tarantool\Client\Handler\Handler;
use Tarantool\Client\Keys;
use Tarantool\Client\Middleware\CustomErrorMiddleware;
use Tarantool\Client\Request\PingRequest;
use Tarantool\Client\Response;
use Tarantool\PhpUnit\Client\TestDoubleFactory;

final class CustomErrorMiddlewareTest extends TestCase
{
    /**
     * @var Handler|MockObject
     */
    private $handler;

    protected function setUp() : void
    {
        $this->handler = $this->createMock(Handler::class);
    }

    public function testFromNamespace() : void
    {
        $errorMessage = 'Error message';
        $errorCode = 42;

        $this->handler->expects($this->once())->method('handle')
            ->willThrowException(RequestFailed::fromErrorResponse(
                TestDoubleFactory::createResponse([
                    Keys::ERROR_24 => $errorMessage,
                    Keys::ERROR => [
                        Keys::ERROR_STACK => [
                            [
                                Keys::ERROR_TYPE => 'CustomError',
                                Keys::ERROR_FILE => 'file.lua',
                                Keys::ERROR_LINE => 1,
                                Keys::ERROR_MESSAGE => $errorMessage,
                                Keys::ERROR_NUMBER => 2,
                                Keys::ERROR_CODE => $errorCode,
                                Keys::ERROR_FIELDS => ['custom_type' => 'RequestDenied'],
                            ],
                        ],
                    ],
                ], [
                    Keys::CODE => Response::TYPE_ERROR + $errorCode,
                ])
            ));

        $middleware = CustomErrorMiddleware::fromNamespace('Tarantool\Client\Exception');

        $this->expectException(RequestDenied::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($errorCode);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testFromFactory() : void
    {
        $errorMessage = 'Error message';
        $errorCode = 42;

        $this->handler->expects($this->once())->method('handle')
            ->willThrowException(RequestFailed::fromErrorResponse(
                TestDoubleFactory::createResponse([
                    Keys::ERROR_24 => $errorMessage,
                    Keys::ERROR => [
                        Keys::ERROR_STACK => [
                            [
                                Keys::ERROR_TYPE => 'CustomError',
                                Keys::ERROR_FILE => 'file.lua',
                                Keys::ERROR_LINE => 1,
                                Keys::ERROR_MESSAGE => $errorMessage,
                                Keys::ERROR_NUMBER => 2,
                                Keys::ERROR_CODE => $errorCode,
                                Keys::ERROR_FIELDS => ['custom_type' => 'MyCustomErrorType'],
                            ],
                        ],
                    ],
                ], [
                    Keys::CODE => Response::TYPE_ERROR + $errorCode,
                ])
            ));

        $middleware = CustomErrorMiddleware::fromFactory(static function (Error $err, RequestFailed $ex) {
            return 'MyCustomErrorType' === $err->tryGetField('custom_type')
                ? new \RuntimeException('My custom exception', 3)
                : $ex;
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('My custom exception');
        $this->expectExceptionCode(3);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testFromMapping() : void
    {
        $errorMessage = 'Error message';
        $errorCode = 42;

        $this->handler->expects($this->once())->method('handle')
            ->willThrowException(RequestFailed::fromErrorResponse(
                TestDoubleFactory::createResponse([
                    Keys::ERROR_24 => $errorMessage,
                    Keys::ERROR => [
                        Keys::ERROR_STACK => [
                            [
                                Keys::ERROR_TYPE => 'CustomError',
                                Keys::ERROR_FILE => 'file.lua',
                                Keys::ERROR_LINE => 1,
                                Keys::ERROR_MESSAGE => $errorMessage,
                                Keys::ERROR_NUMBER => 2,
                                Keys::ERROR_CODE => $errorCode,
                                Keys::ERROR_FIELDS => ['custom_type' => 'MyCustomErrorType'],
                            ],
                        ],
                    ],
                ], [
                    Keys::CODE => Response::TYPE_ERROR + $errorCode,
                ])
            ));

        $middleware = CustomErrorMiddleware::fromMapping([
            'MyCustomErrorType' => \RuntimeException::class,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage($errorMessage);
        $this->expectExceptionCode($errorCode);

        $middleware->process(new PingRequest(), $this->handler);
    }

    public function testFromMappingDetectsNestedErrorType() : void
    {
        $errorMessage = 'Error message';
        $errorCode = 42;

        $this->handler->expects($this->once())->method('handle')
            ->willThrowException(RequestFailed::fromErrorResponse(
                TestDoubleFactory::createResponse([
                    Keys::ERROR_24 => $errorMessage,
                    Keys::ERROR => [
                        Keys::ERROR_STACK => [
                            [
                                Keys::ERROR_TYPE => 'CustomError',
                                Keys::ERROR_FILE => 'file1.lua',
                                Keys::ERROR_LINE => 1,
                                Keys::ERROR_MESSAGE => $errorMessage,
                                Keys::ERROR_NUMBER => 2,
                                Keys::ERROR_CODE => $errorCode,
                                Keys::ERROR_FIELDS => ['custom_type' => 'MyCustomErrorType1'],
                            ],
                            [
                                Keys::ERROR_TYPE => 'CustomError',
                                Keys::ERROR_FILE => 'file2.lua',
                                Keys::ERROR_LINE => 11,
                                Keys::ERROR_MESSAGE => 'Error Message 2',
                                Keys::ERROR_NUMBER => 12,
                                Keys::ERROR_CODE => 13,
                                Keys::ERROR_FIELDS => ['custom_type' => 'MyCustomErrorType2'],
                            ],
                        ],
                    ],
                ], [
                    Keys::CODE => Response::TYPE_ERROR + $errorCode,
                ])
            ));

        $middleware = CustomErrorMiddleware::fromMapping([
            'MyCustomErrorType2' => \RuntimeException::class,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error Message 2');
        $this->expectExceptionCode(13);

        $middleware->process(new PingRequest(), $this->handler);
    }
}

<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Database\Database;
use WebFramework\Middleware\TransactionMiddleware;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\TransactionMiddleware
 */
final class TransactionMiddlewareTest extends Unit
{
    public function testConstructor()
    {
        $database = $this->makeEmpty(Database::class);
        $middleware = new TransactionMiddleware($database);
        verify($middleware)->instanceOf(TransactionMiddleware::class);
    }

    public function testTransactionMiddleware()
    {
        $middleware = $this->make(
            TransactionMiddleware::class,
            [
                'database' => $this->makeEmpty(
                    Database::class,
                    [
                        'startTransaction' => Expected::once(),
                        'commitTransaction' => Expected::once(),
                        'rollbackTransaction' => Expected::never(),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
        );

        $middleware->process($request, $handler);
    }

    public function testRollsBackAndRethrowsWhenHandlerThrows()
    {
        $exception = new \RuntimeException('handler failed');

        $middleware = new TransactionMiddleware(
            $this->makeEmpty(
                Database::class,
                [
                    'startTransaction' => Expected::once(),
                    'commitTransaction' => Expected::never(),
                    'rollbackTransaction' => Expected::once(),
                ]
            )
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => function () use ($exception) {
                    throw $exception;
                },
            ]
        );

        $caught = null;

        try
        {
            $middleware->process($this->makeEmpty(Request::class), $handler);
        }
        catch (\Throwable $e)
        {
            $caught = $e;
        }

        verify($caught)->same($exception);
    }

    public function testRethrowsOriginalExceptionWhenRollbackFails()
    {
        $exception = new \RuntimeException('handler failed');

        $middleware = new TransactionMiddleware(
            $this->makeEmpty(
                Database::class,
                [
                    'rollbackTransaction' => Expected::once(function () {
                        throw new \RuntimeException('rollback failed');
                    }),
                ]
            )
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => function () use ($exception) {
                    throw $exception;
                },
            ]
        );

        $caught = null;

        try
        {
            $middleware->process($this->makeEmpty(Request::class), $handler);
        }
        catch (\Throwable $e)
        {
            $caught = $e;
        }

        verify($caught)->same($exception);
    }
}

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
}

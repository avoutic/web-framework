<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use WebFramework\Middleware\LoggedInMiddleware;

/**
 * @internal
 *
 * @coversNothing
 */
final class LoggedInMiddlewareTest extends Unit
{
    public function testThrowForbiddenExceptionIfAuthenticationMiddlewareHasNotRunYet()
    {
        $middleware = $this->make(
            LoggedInMiddleware::class,
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => Expected::once(null),
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'AuthenticationMiddleware has not run yet');
    }

    public function testThrowForbiddenExceptionIfUserIsNotAuthenticated()
    {
        $middleware = $this->make(
            LoggedInMiddleware::class,
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => Expected::once(false),
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(HttpForbiddenException::class);
    }

    public function testPassIfUserIsAuthenticated()
    {
        $middleware = $this->make(
            LoggedInMiddleware::class,
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => Expected::once(true),
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }
}

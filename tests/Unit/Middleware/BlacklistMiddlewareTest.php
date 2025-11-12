<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Exception\BlacklistException;
use WebFramework\Middleware\BlacklistMiddleware;
use WebFramework\Security\BlacklistService;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\BlacklistMiddleware
 */
final class BlacklistMiddlewareTest extends Unit
{
    public function testThrowExceptionIfIpMiddlewareHasNotRun()
    {
        $middleware = $this->make(
            BlacklistMiddleware::class,
            [
                'blacklistService' => $this->makeEmpty(BlacklistService::class),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return ($name === 'is_authenticated') ? true : null;
                },
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'IpMiddleware must run first');
    }

    public function testThrowExceptionIfAuthenticationMiddlewareHasNotRun()
    {
        $middleware = $this->make(
            BlacklistMiddleware::class,
            [
                'blacklistService' => $this->makeEmpty(BlacklistService::class),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return ($name === 'ip') ? '127.0.0.1' : null;
                },
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'AuthenticationMiddleware must run first');
    }

    public function testThrowBlacklistExceptionIfUserIsBlacklisted()
    {
        $middleware = $this->make(
            BlacklistMiddleware::class,
            [
                'blacklistService' => $this->makeEmpty(
                    BlacklistService::class,
                    [
                        'isBlacklisted' => Expected::once(true),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return match ($name)
                    {
                        'ip' => '127.0.0.1',
                        'is_authenticated' => true,
                        'authenticated_user_id' => 1,
                        default => null,
                    };
                },
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(BlacklistException::class);
    }

    public function testPassIfUserIsNotBlacklisted()
    {
        $middleware = $this->make(
            BlacklistMiddleware::class,
            [
                'blacklistService' => $this->makeEmpty(
                    BlacklistService::class,
                    [
                        'isBlacklisted' => Expected::once(false),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return match ($name)
                    {
                        'ip' => '127.0.0.1',
                        'is_authenticated' => true,
                        'authenticated_user_id' => 1,
                        default => null,
                    };
                },
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(
                    $this->makeEmpty(Response::class)
                ),
            ]
        );

        $middleware->process($request, $handler);
    }
}

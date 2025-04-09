<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Middleware\RequestServiceMiddleware;
use WebFramework\Support\RequestService;

/**
 * @internal
 *
 * @coversNothing
 */
final class RequestServiceMiddlewareTest extends Unit
{
    public function testSetRequest()
    {
        $middleware = $this->make(
            RequestServiceMiddleware::class,
            [
                'requestService' => $this->makeEmpty(
                    RequestService::class,
                    [
                        'setRequest' => Expected::once(),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return ($name === 'ip' || $name === 'is_authenticated' || $name === 'passed_csrf') ? true : null;
                },
            ]
        );
        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }

    public function testThrowExceptionIfIpAttributeIsMissing()
    {
        $middleware = $this->make(
            RequestServiceMiddleware::class,
            [
                'requestService' => $this->makeEmpty(
                    RequestService::class,
                    [
                        'setRequest' => Expected::never(),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return ($name === 'is_authenticated' || $name === 'passed_csrf') ? true : null;
                },
            ]
        );
        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'IpMiddleware must run first');
    }

    public function testThrowExceptionIfIsAuthenticatedAttributeIsMissing()
    {
        $middleware = $this->make(
            RequestServiceMiddleware::class,
            [
                'requestService' => $this->makeEmpty(
                    RequestService::class,
                    [
                        'setRequest' => Expected::never(),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return ($name === 'ip' || $name === 'passed_csrf') ? true : null;
                },
            ]
        );
        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'AuthenticationMiddleware must run first');
    }

    public function testThrowExceptionIfPassedCsrfAttributeIsMissing()
    {
        $middleware = $this->make(
            RequestServiceMiddleware::class,
            [
                'requestService' => $this->makeEmpty(
                    RequestService::class,
                    [
                        'setRequest' => Expected::never(),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => function ($name) {
                    return ($name === 'ip' || $name === 'is_authenticated') ? true : null;
                },
            ]
        );
        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'CsrfValidationMiddleware must run first');
    }
}

<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Entity\User;
use WebFramework\Middleware\AdminUserMiddleware;
use WebFramework\Security\UserRightService;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\AdminUserMiddleware
 */
final class AdminUserMiddlewareTest extends Unit
{
    public function testConstructor()
    {
        $userRightService = $this->makeEmpty(UserRightService::class);
        $middleware = new AdminUserMiddleware($userRightService);
        verify($middleware)->instanceOf(AdminUserMiddleware::class);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function buildRequest(array $attributes): Request
    {
        return $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => fn ($name, $default = null) => $attributes[$name] ?? $default,
            ]
        );
    }

    public function testThrowRuntimeExceptionIfAuthenticationMiddlewareHasNotRun()
    {
        $middleware = $this->make(
            AdminUserMiddleware::class,
        );

        $request = $this->buildRequest([]);

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(\RuntimeException::class, 'AuthenticationMiddleware has not run yet');
    }

    public function testThrowUnauthorizedExceptionIfUserIsNotAuthenticated()
    {
        $middleware = $this->make(
            AdminUserMiddleware::class,
            [
                'userRightService' => $this->makeEmpty(
                    UserRightService::class,
                    [
                        'hasRight' => Expected::never(),
                    ]
                ),
            ]
        );

        $request = $this->buildRequest(['is_authenticated' => false]);

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::never(),
            ]
        );

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(HttpUnauthorizedException::class);
    }

    public function testThrowForbiddenExceptionIfUserDoesNotHaveAdminRight()
    {
        $middleware = $this->make(
            AdminUserMiddleware::class,
            [
                'userRightService' => $this->makeEmpty(
                    UserRightService::class,
                    [
                        'hasRight' => Expected::once(false),
                    ]
                ),
            ]
        );

        $request = $this->buildRequest([
            'is_authenticated' => true,
            'authenticated_user' => $this->makeEmpty(User::class),
        ]);

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        verify(function () use ($middleware, $request, $handler) {
            $middleware->process($request, $handler);
        })->callableThrows(HttpForbiddenException::class);
    }

    public function testPassIfUserHasAdminRight()
    {
        $middleware = $this->make(
            AdminUserMiddleware::class,
            [
                'userRightService' => $this->makeEmpty(
                    UserRightService::class,
                    [
                        'hasRight' => Expected::once(true),
                    ]
                ),
            ]
        );

        $request = $this->buildRequest([
            'is_authenticated' => true,
            'authenticated_user' => $this->makeEmpty(User::class),
        ]);

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

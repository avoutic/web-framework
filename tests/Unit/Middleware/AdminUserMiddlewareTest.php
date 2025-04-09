<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use WebFramework\Entity\User;
use WebFramework\Middleware\AdminUserMiddleware;
use WebFramework\Security\UserRightService;

/**
 * @internal
 *
 * @coversNothing
 */
final class AdminUserMiddlewareTest extends Unit
{
    public function testThrowForbiddenExceptionIfUserIsNotAuthenticated()
    {
        $middleware = $this->make(
            AdminUserMiddleware::class,
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

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => Expected::once($this->makeEmpty(User::class)),
            ]
        );

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

        $request = $this->makeEmpty(
            Request::class,
            [
                'getAttribute' => Expected::once($this->makeEmpty(User::class)),
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

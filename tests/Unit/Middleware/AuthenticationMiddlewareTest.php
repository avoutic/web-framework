<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Entity\User;
use WebFramework\Middleware\AuthenticationMiddleware;
use WebFramework\Security\AuthenticationService;

/**
 * @internal
 *
 * @coversNothing
 */
final class AuthenticationMiddlewareTest extends Unit
{
    public function testNotAuthenticated()
    {
        $middleware = $this->make(
            AuthenticationMiddleware::class,
            [
                'authenticationService' => $this->makeEmpty(
                    AuthenticationService::class,
                    [
                        'isAuthenticated' => Expected::once(false),
                        'getAuthenticatedUser' => Expected::never(),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('is_authenticated'))->equals(false);
                    verify($request->getAttribute('authenticated_user'))->equals(null);
                    verify($request->getAttribute('authenticated_user_id'))->equals(null);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testAuthenticated()
    {
        $user = $this->makeEmpty(
            User::class,
            [
                'getId' => Expected::once(1),
            ]
        );

        $middleware = $this->make(
            AuthenticationMiddleware::class,
            [
                'authenticationService' => $this->makeEmpty(
                    AuthenticationService::class,
                    [
                        'isAuthenticated' => Expected::once(true),
                        'getAuthenticatedUser' => Expected::once($user),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) use ($user) {
                    verify($request->getAttribute('is_authenticated'))->equals(true);
                    verify($request->getAttribute('authenticated_user'))->equals($user);
                    verify($request->getAttribute('authenticated_user_id'))->equals(1);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }
}

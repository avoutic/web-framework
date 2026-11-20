<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Entity\User;
use WebFramework\Middleware\AuthenticationMiddleware;
use WebFramework\Security\AccountStatusChecker;
use WebFramework\Security\AuthenticationService;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\AuthenticationMiddleware
 */
final class AuthenticationMiddlewareTest extends Unit
{
    public function testConstructor()
    {
        $authenticationService = $this->makeEmpty(AuthenticationService::class);
        $middleware = new AuthenticationMiddleware($this->makeEmpty(AccountStatusChecker::class), $authenticationService);
        verify($middleware)->instanceOf(AuthenticationMiddleware::class);
    }

    public function testNotAuthenticated()
    {
        $middleware = $this->make(
            AuthenticationMiddleware::class,
            [
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
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
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
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

    public function testDisabledAccountLosesItsSessions()
    {
        $user = $this->makeEmpty(User::class, ['getId' => 1]);

        $middleware = $this->make(
            AuthenticationMiddleware::class,
            [
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => false]),
                'authenticationService' => $this->makeEmpty(
                    AuthenticationService::class,
                    [
                        'isAuthenticated' => Expected::once(true),
                        'getAuthenticatedUser' => Expected::once($user),
                        'invalidateSessions' => Expected::once(function ($userId) {
                            verify($userId)->equals(1);
                        }),
                        'deauthenticate' => Expected::once(),
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
                    verify($request->getAttribute('authenticated_user'))->null();
                    verify($request->getAttribute('authenticated_user_id'))->null();

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }
}

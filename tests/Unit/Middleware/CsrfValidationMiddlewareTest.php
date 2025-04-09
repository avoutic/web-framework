<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\MessageService;
use WebFramework\Middleware\CsrfValidationMiddleware;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\CsrfService;

/**
 * @internal
 *
 * @coversNothing
 */
final class CsrfValidationMiddlewareTest extends Unit
{
    public function testGetNoCsrf()
    {
        $middleware = $this->make(
            CsrfValidationMiddleware::class,
            [
                'csrfService' => $this->makeEmpty(CsrfService::class),
            ]
        );

        $request = $this->construct(
            Request::class,
            [
                'GET',
                'http://example.com',
                [
                    'token' => '1234567890',
                ],
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('passed_csrf'))->equals(false);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $response = $middleware->process($request, $handler);
    }

    public function testPostWithCsrf()
    {
        $middleware = $this->make(
            CsrfValidationMiddleware::class,
            [
                'csrfService' => $this->makeEmpty(
                    CsrfService::class,
                    [
                        'validateToken' => Expected::once(true),
                    ]
                ),
            ]
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
                [
                    'token' => '1234567890',
                ],
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('passed_csrf'))->equals(true);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $response = $middleware->process($request, $handler);
    }

    public function testPostWithInvalidCsrf()
    {
        $middleware = $this->make(
            CsrfValidationMiddleware::class,
            [
                'blacklistService' => $this->makeEmpty(
                    BlacklistService::class,
                    [
                        'addEntry' => Expected::once(),
                    ]
                ),
                'csrfService' => $this->makeEmpty(
                    CsrfService::class,
                    [
                        'validateToken' => Expected::once(false),
                    ]
                ),
                'messageService' => $this->makeEmpty(
                    MessageService::class,
                    [
                        'add' => Expected::once(),
                    ]
                ),
            ]
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
                [
                    'token' => '1234567890',
                ],
            ]
        );

        $request = $request->withAttribute('ip', '127.0.0.1');
        $request = $request->withAttribute('is_authenticated', true);
        $request = $request->withAttribute('authenticated_user_id', 1);

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('passed_csrf'))->equals(false);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $response = $middleware->process($request, $handler);
    }
}

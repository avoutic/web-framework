<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Middleware\IpMiddleware;

/**
 * @internal
 *
 * @coversNothing
 */
final class IpMiddlewareTest extends Unit
{
    public function testIpMiddleware()
    {
        $middleware = $this->make(
            IpMiddleware::class,
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
                ['Content-Type' => 'application/json'],
                '{"name": "John", "age": 30}',
                '1.1',
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('ip'))->equals('127.0.0.1');

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testIpMiddlewareWithApp()
    {
        $middleware = $this->make(
            IpMiddleware::class,
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('ip'))->equals('app');

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }
}

<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Middleware\SecurityHeadersMiddleware;

/**
 * @internal
 *
 * @coversNothing
 */
final class SecurityHeadersMiddlewareTest extends Unit
{
    public function testAddRandomHeader()
    {
        $middleware = $this->make(
            SecurityHeadersMiddleware::class,
        );

        $request = $this->makeEmpty(
            Request::class,
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(
                    $this->construct(Response::class)
                ),
            ]
        );

        $response = $middleware->process($request, $handler);

        verify($response->getHeaderLine('X-Random'))->notEmpty();
        verify($response->getHeaderLine('X-Frame-Options'))->equals('SAMEORIGIN');
    }
}

<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\ServerRequest;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Middleware\RequestDecoratorMiddleware;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\RequestDecoratorMiddleware
 */
final class RequestDecoratorMiddlewareTest extends Unit
{
    public function testPlainRequestIsDecorated()
    {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', 'https://example.com/verify?guid=abc')
            ->withQueryParams(['guid' => 'abc'])
            ->withAttribute('ip', '10.0.0.1')
        ;

        verify($request)->notInstanceOf(ServerRequest::class);

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (ServerRequestInterface $request) {
                    verify($request)->instanceOf(ServerRequest::class);

                    // The methods the framework's actions rely on, and what was already on the request
                    verify($request->getParam('guid'))->equals('abc');
                    verify($request->getAttribute('ip'))->equals('10.0.0.1');
                    verify($request->getUri()->getPath())->equals('/verify');

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        (new RequestDecoratorMiddleware())->process($request, $handler);
    }

    public function testDecoratedRequestIsPassedOnAsIs()
    {
        $request = new ServerRequest((new ServerRequestFactory())->createServerRequest('GET', 'https://example.com/'));

        // A real handler, as a mocked one receives a clone of the request
        $handler = new class($this->makeEmpty(Response::class)) implements RequestHandlerInterface {
            public ?ServerRequestInterface $received = null;

            public function __construct(private Response $response) {}

            public function handle(ServerRequestInterface $request): Response
            {
                $this->received = $request;

                return $this->response;
            }
        };

        (new RequestDecoratorMiddleware())->process($request, $handler);

        verify($handler->received)->same($request);
    }
}

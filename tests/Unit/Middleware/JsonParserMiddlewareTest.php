<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Middleware\JsonParserMiddleware;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\JsonParserMiddleware
 */
final class JsonParserMiddlewareTest extends Unit
{
    public function testParseJson()
    {
        $middleware = $this->make(
            JsonParserMiddleware::class,
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
                ['Content-Type' => 'application/json'],
                '{"name": "John", "age": 30}',
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('is_json'))->equals(true);
                    verify($request->getAttribute('json_data'))->equals(['name' => 'John', 'age' => 30]);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testNoJson()
    {
        $middleware = $this->make(
            JsonParserMiddleware::class,
        );

        $request = new Request(
            'POST',
            'http://example.com',
            ['Content-Type' => 'text/html'],
            '{"name": "John", "age": 30}',
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('is_json'))->equals(false);
                    verify($request->getAttribute('json_data'))->equals(null);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testInvalidJson()
    {
        $middleware = $this->make(
            JsonParserMiddleware::class,
        );

        $request = new Request(
            'POST',
            'http://example.com',
            ['Content-Type' => 'application/json'],
            '{"name": "John", "age": 30',
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('is_json'))->equals(true);
                    verify($request->getAttribute('json_data'))->equals(null);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testBodyCanStillBeReadAfterParsing()
    {
        $middleware = new JsonParserMiddleware();
        $body = '{"name": "John", "age": 30}';

        $request = new Request('POST', 'http://example.com', ['Content-Type' => 'application/json'], $body);

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) use ($body) {
                    // As a webhook handler does to verify a signature over the raw body
                    verify($request->getBody()->getContents())->same($body);

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }
}

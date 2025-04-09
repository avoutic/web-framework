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
 * @coversNothing
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
}

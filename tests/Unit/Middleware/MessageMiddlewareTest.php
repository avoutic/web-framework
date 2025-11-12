<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Middleware\MessageMiddleware;
use WebFramework\Presentation\MessageService;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\MessageMiddleware
 */
final class MessageMiddlewareTest extends Unit
{
    public function testAddMessageFromUrl()
    {
        $middleware = $this->make(
            MessageMiddleware::class,
            [
                'messageService' => $this->makeEmpty(
                    MessageService::class,
                    [
                        'addFromUrl' => Expected::once('test'),
                    ]
                ),
            ]
        );

        $request = $this->makeEmpty(
            Request::class,
            [
                'getQueryParams' => Expected::once(['msg' => 'test']),
            ]
        );

        $handler = $this->makeEmpty(RequestHandlerInterface::class);

        $middleware->process($request, $handler);
    }
}

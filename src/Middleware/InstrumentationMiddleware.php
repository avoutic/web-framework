<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\Instrumentation;

class InstrumentationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Instrumentation $instrumentation,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $span = $this->instrumentation->startSpan('app.handle_request');

        $response = $handler->handle($request);

        $this->instrumentation->finishSpan($span);

        return $response;
    }
}

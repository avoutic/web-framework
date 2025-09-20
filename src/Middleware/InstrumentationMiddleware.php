<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use WebFramework\Diagnostics\Instrumentation;

/**
 * Middleware to handle instrumentation for performance monitoring.
 */
class InstrumentationMiddleware implements MiddlewareInterface
{
    /**
     * @param Instrumentation $instrumentation The instrumentation service
     */
    public function __construct(
        private Instrumentation $instrumentation,
    ) {}

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        if ($route !== null)
        {
            $transaction = $this->instrumentation->getCurrentTransaction();
            $this->instrumentation->setTransactionName($transaction, $route->getPattern());
        }

        $span = $this->instrumentation->startSpan('app.handle_request');

        $response = $handler->handle($request);

        $this->instrumentation->finishSpan($span);

        return $response;
    }
}

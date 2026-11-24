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
use Slim\Http\ServerRequest;

/**
 * Middleware to make sure the request is a decorated Slim\Http\ServerRequest.
 *
 * The actions, extension hooks and events of the framework work with Slim\Http\ServerRequest.
 * A request that enters through App::run() is decorated by Slim. A request that is passed
 * to App::handle() directly, for example by a test harness or another PSR-15 client, is
 * not. Add this middleware as the outermost middleware to decorate those requests.
 */
class RequestDecoratorMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$request instanceof ServerRequest)
        {
            $request = new ServerRequest($request);
        }

        return $handler->handle($request);
    }
}

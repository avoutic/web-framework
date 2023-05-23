<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Core\ResponseEmitter;

class ErrorRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseEmitter $response_emitter,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try
        {
            return $next->handle($request);
        }
        catch (HttpForbiddenException $e)
        {
            return $this->response_emitter->forbidden($request);
        }
        catch (HttpNotFoundException $e)
        {
            return $this->response_emitter->not_found($request);
        }
        catch (HttpUnauthorizedException $e)
        {
            return $this->response_emitter->unauthorized($request);
        }
    }
}

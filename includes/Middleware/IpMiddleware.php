<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class IpMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $server_params = $request->getServerParams();

        $ip = (isset($server_params['REMOTE_ADDR'])) ? $server_params['REMOTE_ADDR'] : 'app';
        $request = $request->withAttribute('ip', $ip);

        return $handler->handle($request);
    }
}

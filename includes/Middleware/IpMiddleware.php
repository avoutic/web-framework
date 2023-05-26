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
        $serverParams = $request->getServerParams();

        $ip = (isset($serverParams['REMOTE_ADDR'])) ? $serverParams['REMOTE_ADDR'] : 'app';
        $request = $request->withAttribute('ip', $ip);

        return $handler->handle($request);
    }
}

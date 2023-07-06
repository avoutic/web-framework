<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        // Add random header (against BREACH like attacks)
        //
        $response = $response->withHeader('X-Random', substr(sha1((string) time()), 0, mt_rand(1, 40)));

        // Add Clickjack prevention header
        //
        return $response->withHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}

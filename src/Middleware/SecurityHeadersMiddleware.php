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

/**
 * Middleware to add security headers to the response.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
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

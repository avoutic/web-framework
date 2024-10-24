<?php

/**
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
 * Middleware to add the client IP address to the request attributes.
 */
class IpMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $serverParams = $request->getServerParams();

        $ip = (isset($serverParams['REMOTE_ADDR'])) ? $serverParams['REMOTE_ADDR'] : 'app';
        $request = $request->withAttribute('ip', $ip);

        return $handler->handle($request);
    }
}

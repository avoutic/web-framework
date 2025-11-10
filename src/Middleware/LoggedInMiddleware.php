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
use Slim\Exception\HttpUnauthorizedException;

/**
 * Middleware to check if there is an authenticated user.
 *
 * Requires the AuthenticationMiddleware to run first.
 */
class LoggedInMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $isAuthenticated = $request->getAttribute('is_authenticated');

        if ($isAuthenticated === null)
        {
            throw new \RuntimeException('AuthenticationMiddleware has not run yet');
        }

        if (!$isAuthenticated)
        {
            throw new HttpUnauthorizedException($request);
        }

        return $handler->handle($request);
    }
}

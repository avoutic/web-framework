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
use Slim\Exception\HttpForbiddenException;
use WebFramework\Security\UserRightService;

/**
 * Middleware to check if the authenticated user had the 'admin' right.
 *
 * Requires the AuthenticationMiddleware to run first.
 */
class AdminUserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserRightService $userRightService,
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $authenticatedUser = $request->getAttribute('authenticated_user');

        if ($authenticatedUser === null)
        {
            throw new \RuntimeException('AuthenticationMiddleware has not run yet');
        }

        if (!$this->userRightService->hasRight($authenticatedUser, 'admin'))
        {
            throw new HttpForbiddenException($request);
        }

        return $handler->handle($request);
    }
}

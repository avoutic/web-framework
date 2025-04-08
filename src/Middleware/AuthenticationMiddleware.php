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
use WebFramework\Security\AuthenticationService;

/**
 * Middleware to check if the user is authenticated.
 *
 * Adds the 'is_authenticated', 'authenticated_user', and 'authenticated_user_id' attributes to the request.
 */
class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthenticationService $authenticationService,
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $isAuthenticated = $this->authenticationService->isAuthenticated();

        $request = $request->withAttribute('is_authenticated', $isAuthenticated);

        if ($isAuthenticated)
        {
            $authenticatedUser = $this->authenticationService->getAuthenticatedUser();
            $request = $request
                ->withAttribute('authenticated_user', $authenticatedUser)
                ->withAttribute('authenticated_user_id', $authenticatedUser->getId())
            ;
        }

        return $handler->handle($request);
    }
}

<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Security\AuthenticationService;

class AuthenticationInfoMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthenticationService $authenticationService,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $user = null;
        $userId = null;

        if ($this->authenticationService->isAuthenticated())
        {
            $user = $this->authenticationService->getAuthenticatedUser();
            $userId = $user->id;
        }

        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('user_id', $userId);

        return $handler->handle($request);
    }
}

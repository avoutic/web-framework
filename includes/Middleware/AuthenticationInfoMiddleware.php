<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\Security\AuthenticationService;

class AuthenticationInfoMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthenticationService $authentication_service,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $user = null;
        $user_id = null;

        if ($this->authentication_service->is_authenticated())
        {
            $user = $this->authentication_service->get_authenticated_user();
            $user_id = $user->id;
        }

        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('user_id', $user_id);

        return $handler->handle($request);
    }
}

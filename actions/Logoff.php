<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Security\AuthenticationService;

class Logoff
{
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected ResponseEmitter $responseEmitter,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $this->authenticationService->deauthenticate();

        return $this->responseEmitter->buildRedirect('/');
    }
}

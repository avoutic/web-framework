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
        $returnPage = $request->getParam('return_page');
        if (preg_match('/^\\s*'.FORMAT_RETURN_PAGE.'\\s*$/m', $returnPage) !== 1)
        {
            $returnPage = '';
        }

        $this->authenticationService->deauthenticate();

        if (!strlen($returnPage) || substr($returnPage, 0, 2) == '//')
        {
            $returnPage = '/';
        }

        if (substr($returnPage, 0, 1) != '/')
        {
            $returnPage = '/'.$returnPage;
        }

        return $this->responseEmitter->buildRedirect($returnPage);
    }
}

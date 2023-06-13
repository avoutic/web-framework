<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\ValidatorService;
use WebFramework\Security\AuthenticationService;

class Logoff
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected ValidatorService $validatorService,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $filtered = $this->validatorService->getFilteredParams($request, [
            'return_page' => FORMAT_RETURN_PAGE,
        ]);

        $this->authenticationService->deauthenticate();

        $returnPage = $filtered['return_page'];

        if (!strlen($returnPage) || substr($returnPage, 0, 2) == '//')
        {
            $returnPage = '/';
        }

        if (substr($returnPage, 0, 1) != '/')
        {
            $returnPage = '/'.$returnPage;
        }

        $baseUrl = $this->configService->get('base_url');

        return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}");
    }
}

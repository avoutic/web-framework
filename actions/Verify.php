<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Security\UserVerificationService;

class Verify
{
    public function __construct(
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected UserVerificationService $userVerificationService,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $loginPage = $this->configService->get('actions.login.location');

        try
        {
            $this->userVerificationService->handleVerify($request->getParam('code', ''));

            $afterVerifyPage = $this->configService->get('actions.login.after_verify_page');

            return $this->responseEmitter->buildQueryRedirect(
                $loginPage,
                [],
                ['return_page' => $afterVerifyPage],
                'success',
                'verify.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $loginPage,
                [],
                'error',
                'verify.link_expired',
            );
        }
    }
}

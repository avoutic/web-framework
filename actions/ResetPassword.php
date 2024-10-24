<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Security\ResetPasswordService;

class ResetPassword
{
    public function __construct(
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected ResetPasswordService $resetPasswordService,
    ) {}

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        try
        {
            $this->resetPasswordService->handlePasswordReset($request->getParam('code', ''));

            // Redirect to main screen
            //
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'success',
                'reset_password.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.forgot_password.location'),
                [],
                'error',
                'reset_password.link_expired',
            );
        }
    }
}

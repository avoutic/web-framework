<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\WrongAccountException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangeEmailService;

class ChangeEmailVerify
{
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected ResponseEmitter $responseEmitter,
        protected ChangeEmailService $changeEmailService,
    ) {}

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        $user = $this->authenticationService->getAuthenticatedUser();

        $changePage = $this->configService->get('actions.change_email.location');
        $returnPage = $this->configService->get('actions.change_email.return_page');

        try
        {
            $this->changeEmailService->handleChangeEmailVerify($user, $request->getParam('code', ''));

            return $this->responseEmitter->buildRedirect(
                $returnPage,
                [],
                'success',
                'change_email.success',
            );
        }
        catch (CodeVerificationException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $changePage,
                [],
                'error',
                'change_email.link_expired',
            );
        }
        catch (WrongAccountException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'error',
                'change_email.other_account',
            );
        }
        catch (DuplicateEmailException $e)
        {
            return $this->responseEmitter->buildRedirect(
                $changePage,
                [],
                'error',
                'change_email.duplicate',
            );
        }
    }
}

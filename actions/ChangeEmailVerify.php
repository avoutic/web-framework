<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserCodeService;
use WebFramework\Core\UserEmailService;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\SecurityIteratorService;

class ChangeEmailVerify
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected ResponseEmitter $responseEmitter,
        protected SecurityIteratorService $securityIteratorService,
        protected UserCodeService $userCodeService,
        protected UserEmailService $userEmailService,
        protected UserRepository $userRepository,
    ) {
    }

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
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $request->getParam('code', ''),
                validity: 10 * 60,
                action: 'change_email',
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

        // Check user status
        //
        $codeUser = $this->userRepository->getObjectById($codeUserId);
        if ($codeUser === null)
        {
            return $this->responseEmitter->buildRedirect(
                $changePage,
                [],
                'error',
                'change_email.link_expired',
            );
        }

        $email = $verifyParams['email'];

        // Only allow for current user
        //
        if ($codeUser->getId() !== $user->getId())
        {
            $this->authenticationService->deauthenticate();

            return $this->responseEmitter->buildRedirect(
                $this->configService->get('actions.login.location'),
                [],
                'error',
                'change_email.other_account',
            );
        }

        // Already changed
        //
        if ($user->getEmail() === $email)
        {
            return $this->responseEmitter->buildRedirect(
                $returnPage,
                [],
                'success',
                'change_email.success',
            );
        }

        // Change email
        //
        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
            return $this->responseEmitter->buildRedirect(
                $changePage,
                [],
                'error',
                'change_email.link_expired',
            );
        }

        try
        {
            $this->userEmailService->changeEmail($user, $email);
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

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);

        // Redirect to verification request screen
        //
        return $this->responseEmitter->buildRedirect(
            $returnPage,
            [],
            'success',
            'change_email.success',
        );
    }
}

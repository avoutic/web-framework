<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserCodeService;
use WebFramework\Core\UserEmailService;
use WebFramework\Core\ValidatorService;
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
        protected ValidatorService $validatorService,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $user = $this->authenticationService->getAuthenticatedUser();

        $filtered = $this->validatorService->getFilteredParams($request, [
            'code' => '.*',
        ]);

        $baseUrl = $this->configService->get('base_url');
        $changePage = $this->configService->get('actions.change_email.location');
        $returnPage = $this->configService->get('actions.change_email.return_page');

        try
        {
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $filtered['code'],
                validity: 10 * 60,
                action: 'change_email',
            );
        }
        catch (CodeVerificationException $e)
        {
            $message = $this->messageService->getForUrl('error', 'E-mail verification link expired');

            return $this->responseEmitter->redirect("{$baseUrl}{$changePage}?{$message}");
        }

        // Check user status
        //
        $codeUser = $this->userRepository->getObjectById($codeUserId);
        if ($codeUser === null)
        {
            $message = $this->messageService->getForUrl('error', 'E-mail verification link expired');

            return $this->responseEmitter->redirect("{$baseUrl}{$changePage}?{$message}");
        }

        $email = $verifyParams['email'];

        // Only allow for current user
        //
        if ($codeUser->getId() !== $user->getId())
        {
            $this->authenticationService->deauthenticate();

            $loginPage = $this->configService->get('actions.login.location');
            $message = $this->messageService->getForUrl('error', 'Other account', 'The link you used is meant for a different account. The current account has been logged off. Please try the link again.');

            return $this->responseEmitter->redirect("{$baseUrl}{$loginPage}?{$message}");
        }

        // Already changed
        //
        if ($user->getEmail() === $email)
        {
            $message = $this->messageService->getForUrl('success', 'E-mail address changed successfully');

            return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$message}");
        }

        // Change email
        //
        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
            $message = $this->messageService->getForUrl('error', 'E-mail verification link expired');

            return $this->responseEmitter->redirect("{$baseUrl}{$changePage}?{$message}");
        }

        try
        {
            $this->userEmailService->changeEmail($user, $email);
        }
        catch (DuplicateEmailException $e)
        {
            $message = $this->messageService->getForUrl('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.');

            return $this->responseEmitter->redirect("{$baseUrl}{$changePage}?{$message}");
        }

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);

        // Redirect to verification request screen
        //
        $message = $this->messageService->getForUrl('success', 'E-mail address changed successfully');

        return $this->responseEmitter->redirect("{$baseUrl}{$returnPage}?{$message}");
    }
}

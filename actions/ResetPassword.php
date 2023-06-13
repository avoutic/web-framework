<?php

namespace WebFramework\Actions;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Core\MessageService;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\UserCodeService;
use WebFramework\Core\UserPasswordService;
use WebFramework\Core\ValidatorService;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\SecurityIteratorService;

class ResetPassword
{
    public function __construct(
        protected Container $container,
        protected AuthenticationService $authenticationService,
        protected ConfigService $configService,
        protected MessageService $messageService,
        protected ResponseEmitter $responseEmitter,
        protected SecurityIteratorService $securityIteratorService,
        protected UserCodeService $userCodeService,
        protected UserPasswordService $userPasswordService,
        protected UserRepository $userRepository,
        protected ValidatorService $validatorService,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): Response
    {
        $filtered = $this->validatorService->getFilteredParams($request, [
            'code' => '.*',
        ]);

        $baseUrl = $this->configService->get('base_url');
        $forgotPasswordPage = $this->configService->get('actions.forgot_password.location');

        try
        {
            ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
                $filtered['code'],
                validity: 10 * 60,
                action: 'reset_password',
            );
        }
        catch (CodeVerificationException $e)
        {
            $message = $this->messageService->getForUrl('error', 'Password reset link expired', 'Please request a new one.');

            return $this->responseEmitter->redirect("{$baseUrl}{$forgotPasswordPage}?{$message}");
        }

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user === null)
        {
            throw new \RuntimeException('User not found');
        }

        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
            $message = $this->messageService->getForUrl('error', 'Password reset link expired', 'Please request a new one.');

            return $this->responseEmitter->redirect("{$baseUrl}{$forgotPasswordPage}?{$message}");
        }

        $this->userPasswordService->sendNewPassword($user);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());

        // Redirect to main sceen
        //
        $loginPage = $this->configService->get('actions.login.location');
        $message = $this->messageService->getForUrl('success', 'Password reset', 'You will receive a mail with your new password');

        return $this->responseEmitter->redirect("{$baseUrl}{$loginPage}?{$message}");
    }
}

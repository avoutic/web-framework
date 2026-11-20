<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Actions;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Config\ConfigService;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;
use WebFramework\Security\AccountStatusChecker;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangeEmailService;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Security\UserVerificationService;

/**
 * Class Verify.
 *
 * This action handles the user verification process.
 */
class VerifyResend
{
    /**
     * Verify constructor.
     *
     * @param AccountStatusChecker       $accountStatusChecker       The account status checker
     * @param AuthenticationService      $authenticationService      The authentication service
     * @param ChangeEmailService         $changeEmailService         The change email service
     * @param ConfigService              $configService              The configuration service
     * @param ResetPasswordService       $resetPasswordService       The reset password service
     * @param ResponseEmitter            $responseEmitter            The response emitter
     * @param UserRepository             $userRepository             The user repository
     * @param UserVerificationService    $userVerificationService    The user verification service
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     */
    public function __construct(
        private AccountStatusChecker $accountStatusChecker,
        private AuthenticationService $authenticationService,
        private ChangeEmailService $changeEmailService,
        private ConfigService $configService,
        private ResetPasswordService $resetPasswordService,
        private ResponseEmitter $responseEmitter,
        private UserRepository $userRepository,
        private UserVerificationService $userVerificationService,
        private VerificationCodeRepository $verificationCodeRepository,
    ) {}

    /**
     * Handle the verification resend request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @uses config actions.login.location
     * @uses config security.email_verification.resend_cooldown_seconds
     * @uses config actions.verify.location
     */
    public function __invoke(Request $request, Response $response, array $routeArgs): ResponseInterface
    {
        if (!$request->getAttribute('passed_csrf'))
        {
            throw new HttpNotFoundException($request);
        }

        $verificationCode = $this->verificationCodeRepository->getByGuid($request->getParam('guid'));
        if ($verificationCode === null)
        {
            throw new HttpNotFoundException($request);
        }

        if ($verificationCode->getAction() === 'change_email')
        {
            if (!$this->authenticationService->isAuthenticated())
            {
                throw new HttpUnauthorizedException($request);
            }
        }

        // Check resend cooldown
        $cooldownSeconds = (int) $this->configService->get('security.email_verification.resend_cooldown_seconds');
        $secondsSinceCreation = Carbon::now()->getTimestamp() - $verificationCode->getCreatedAt();

        if ($secondsSinceCreation < $cooldownSeconds)
        {
            return $this->responseEmitter->buildQueryRedirect(
                $this->configService->get('actions.verify.location'),
                [],
                [
                    'guid' => $verificationCode->getGuid(),
                ],
                'error',
                'verify.resend_cooldown',
            );
        }

        // Get user and flow data before invalidating
        $action = $verificationCode->getAction();
        $flowData = $verificationCode->getFlowData();
        $user = $this->userRepository->find($verificationCode->getUserId());
        if ($user === null)
        {
            throw new \RuntimeException('User not found');
        }

        // A disabled account is not sent new login or password reset codes. Registration
        // codes are still sent, so an account that is disabled until approval can verify
        // its email address. Change email requires a logged in, and thus enabled, user
        if (!$this->accountStatusChecker->isEnabled($user))
        {
            if ($action === 'login')
            {
                return $this->responseEmitter->buildRedirect(
                    $this->configService->get('actions.login.location'),
                    [],
                    'error',
                    'login.account_disabled',
                );
            }

            if ($action === 'reset_password')
            {
                // Don't reveal that the account is disabled - same response as a resend
                return $this->responseEmitter->buildQueryRedirect(
                    $this->configService->get('actions.verify.location'),
                    [],
                    [
                        'guid' => $verificationCode->getGuid(),
                    ],
                    'success',
                    'verify.code_resent',
                );
            }
        }

        // Invalidate old code
        $verificationCode->markAsInvalidated();
        $this->verificationCodeRepository->save($verificationCode);

        // Send email based on action type and get new GUID
        $newGuid = '';
        if ($action === 'login' || $action === 'register')
        {
            // Login or register flow
            if (!isset($flowData['after_verify_data']))
            {
                throw new \RuntimeException('After verify data is required for login or register flow');
            }

            $newGuid = $this->userVerificationService->sendVerifyMail($user, $action, $flowData['after_verify_data']);
        }
        elseif ($action === 'reset_password')
        {
            $newGuid = $this->resetPasswordService->sendPasswordResetMail($user);
        }
        elseif ($action === 'change_email')
        {
            if (!isset($flowData['email']))
            {
                throw new \RuntimeException('Email is required for change email flow');
            }

            $newGuid = $this->changeEmailService->sendChangeEmailVerify($user, $flowData['email']);
        }
        else
        {
            throw new \RuntimeException('Invalid action for verification code');
        }

        return $this->responseEmitter->buildQueryRedirect(
            $this->configService->get('actions.verify.location'),
            [],
            [
                'guid' => $newGuid,
            ],
            'success',
            'verify.code_resent',
        );
    }
}

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
     * @uses config actions.verify.location
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

        // Check 60-second cooldown
        $createdAt = $verificationCode->getCreatedAt();
        $now = Carbon::now()->getTimestamp();
        $secondsSinceCreation = $now - $createdAt;

        if ($secondsSinceCreation < 60)
        {
            $remainingSeconds = 60 - $secondsSinceCreation;

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
        $user = $this->userRepository->getObjectById($verificationCode->getUserId());
        if ($user === null)
        {
            throw new \RuntimeException('User not found');
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

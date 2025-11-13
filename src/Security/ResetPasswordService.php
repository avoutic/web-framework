<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;

/**
 * Handles password reset operations.
 */
class ResetPasswordService
{
    /**
     * ResetPasswordService constructor.
     *
     * @param AuthenticationService      $authenticationService      The authentication service
     * @param LoggerInterface            $logger                     The logger service
     * @param PasswordHashService        $passwordHashService        The password hash service
     * @param RandomProvider             $randomProvider             The random provider service
     * @param SecurityIteratorService    $securityIteratorService    The security iterator service
     * @param UserCodeService            $userCodeService            The user code service
     * @param UserMailer                 $userMailer                 The user mailer service
     * @param UserRepository             $userRepository             The user repository
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private LoggerInterface $logger,
        private PasswordHashService $passwordHashService,
        private RandomProvider $randomProvider,
        private SecurityIteratorService $securityIteratorService,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
        private VerificationCodeRepository $verificationCodeRepository,
        private int $codeExpiryMinutes,
    ) {}

    /**
     * Update a user's password.
     *
     * @param User   $user        The user whose password is being updated
     * @param string $newPassword The new password
     */
    public function updatePassword(User $user, string $newPassword): void
    {
        $this->logger->info('Updating password for user', ['user_id' => $user->getId()]);

        // Change password
        //
        $newHash = $this->passwordHashService->generateHash($newPassword);

        $user->setSolidPassword($newHash);
        $this->userRepository->save($user);

        $this->securityIteratorService->incrementFor($user);
    }

    /**
     * Send a password reset email to a user.
     *
     * @param User $user The user requesting a password reset
     *
     * @return string The GUID for the password reset
     */
    public function sendPasswordResetMail(User $user): string
    {
        $this->logger->debug('Sending password reset mail', ['user_id' => $user->getId()]);

        $securityIterator = $this->securityIteratorService->incrementFor($user);

        ['guid' => $guid, 'code' => $code] = $this->userCodeService->generateVerificationCodeEntry(
            $user,
            'reset_password',
            [
                'iterator' => $securityIterator,
            ]
        );

        $this->userMailer->passwordReset(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'code' => $code,
                'validity' => $this->codeExpiryMinutes,
            ]
        );

        return $guid;
    }

    /**
     * Handle a password reset request.
     *
     * @param Request $request The request that triggered the event
     * @param string  $guid    The GUID of the verification code (already verified)
     *
     * @throws CodeVerificationException If the code is invalid or expired
     */
    public function handleData(Request $request, string $guid): void
    {
        $this->logger->debug('Handling password reset data', ['guid' => $guid]);

        $verificationCode = $this->verificationCodeRepository->getByGuid($guid);

        if ($verificationCode === null)
        {
            throw new CodeVerificationException();
        }

        // Verify the code was actually verified (marked as correct) and hasn't expired
        if (!$verificationCode->isCorrect())
        {
            $this->logger->debug('Verification code not yet correct', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->isExpired())
        {
            $this->logger->debug('Verification code expired', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        // Prevent replay attacks - check if code has already been invalidated or processed
        if ($verificationCode->isInvalidated() || $verificationCode->isProcessed())
        {
            $this->logger->warning('Verification code already invalidated or processed (replay attempt)', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->getAction() !== 'reset_password')
        {
            $this->logger->error('Invalid action for verification code', [
                'guid' => $guid,
                'action' => $verificationCode->getAction(),
            ]);

            throw new CodeVerificationException();
        }

        $user = $this->userRepository->getObjectById($verificationCode->getUserId());
        if ($user === null)
        {
            throw new CodeVerificationException();
        }

        $flowData = $verificationCode->getFlowData();

        $this->logger->debug('Password reset data for user', ['user_id' => $user->getId(), 'flow_data' => $flowData]);

        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($flowData['iterator']) || $securityIterator != $flowData['iterator'])
        {
            $this->logger->debug('Invalid iterator', ['user_id' => $user->getId(), 'iterator' => $securityIterator, 'verify_iterator' => $flowData['iterator'] ?? null]);

            throw new CodeVerificationException();
        }

        // Mark as processed to prevent replay attacks
        $verificationCode->markAsProcessed();
        $this->verificationCodeRepository->save($verificationCode);

        $this->sendNewPassword($user);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
    }

    /**
     * Send a new password to a user.
     *
     * @param User $user The user to send the new password to
     *
     * @return bool|string True if the email was sent successfully, or an error message
     */
    public function sendNewPassword(User $user): bool|string
    {
        $this->logger->info('Sending new password to user', ['user_id' => $user->getId()]);

        // Generate and store password
        //
        $newPw = bin2hex(substr($this->randomProvider->getRandom(24), 0, 10));

        $this->updatePassword($user, $newPw);

        return $this->userMailer->newPassword(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'password' => $newPw,
            ]
        );
    }
}

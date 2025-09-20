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
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\UserRepository;
use WebFramework\Support\UrlBuilder;

/**
 * Handles password reset operations.
 */
class ResetPasswordService
{
    /**
     * ResetPasswordService constructor.
     *
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param ConfigService           $configService           The configuration service
     * @param LoggerInterface         $logger                  The logger service
     * @param PasswordHashService     $passwordHashService     The password hash service
     * @param UrlBuilder              $urlBuilder              The URL builder service
     * @param UserCodeService         $userCodeService         The user code service
     * @param UserMailer              $userMailer              The user mailer service
     * @param UserRepository          $userRepository          The user repository
     * @param SecurityIteratorService $securityIteratorService The security iterator service
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private ConfigService $configService,
        private LoggerInterface $logger,
        private PasswordHashService $passwordHashService,
        private UrlBuilder $urlBuilder,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
        private SecurityIteratorService $securityIteratorService,
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
     * @return bool|string True if the email was sent successfully, or an error message
     */
    public function sendPasswordResetMail(User $user): bool|string
    {
        $this->logger->debug('Sending password reset mail', ['user_id' => $user->getId()]);

        $securityIterator = $this->securityIteratorService->incrementFor($user);

        $code = $this->userCodeService->generate($user, 'reset_password', ['iterator' => $securityIterator]);

        $resetUrl =
            $this->urlBuilder->getServerUrl().
            $this->urlBuilder->buildQueryUrl(
                $this->configService->get('actions.forgot_password.reset_password_page'),
                [],
                ['code' => $code],
            );

        return $this->userMailer->passwordReset(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'reset_url' => $resetUrl,
            ]
        );
    }

    /**
     * Handle a password reset request.
     *
     * @param string $code The password reset code
     *
     * @throws CodeVerificationException If the code is invalid or expired
     */
    public function handlePasswordReset(string $code): void
    {
        $this->logger->debug('Handling password reset code');

        ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
            $code,
            validity: 10 * 60,
            action: 'reset_password',
        );

        $this->logger->debug('Password reset code for user', ['code_user_id' => $codeUserId, 'verify_params' => $verifyParams]);

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user === null)
        {
            $this->logger->debug('User not found', ['code_user_id' => $codeUserId]);

            throw new CodeVerificationException();
        }

        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
            $this->logger->debug('Invalid iterator', ['code_user_id' => $codeUserId, 'iterator' => $securityIterator, 'verify_iterator' => $verifyParams['iterator']]);

            throw new CodeVerificationException();
        }

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
        $newPw = bin2hex(substr(openssl_random_pseudo_bytes(24), 0, 10));

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

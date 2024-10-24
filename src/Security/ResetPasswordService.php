<?php

namespace WebFramework\Security;

use WebFramework\Core\ConfigService;
use WebFramework\Core\UrlBuilder;
use WebFramework\Core\UserMailer;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;

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
        ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
            $code,
            validity: 10 * 60,
            action: 'reset_password',
        );

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user === null)
        {
            throw new CodeVerificationException();
        }

        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
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

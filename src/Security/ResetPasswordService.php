<?php

namespace WebFramework\Security;

use WebFramework\Core\ConfigService;
use WebFramework\Core\UrlBuilder;
use WebFramework\Core\UserMailer;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;

class ResetPasswordService
{
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

    public function updatePassword(User $user, string $newPassword): void
    {
        // Change password
        //
        $newHash = $this->passwordHashService->generateHash($newPassword);

        $user->setSolidPassword($newHash);
        $this->userRepository->save($user);

        $this->securityIteratorService->incrementFor($user);
    }

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

<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Entity\User;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\PasswordHashService;
use WebFramework\Security\SecurityIteratorService;

class UserPasswordService
{
    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private PasswordHashService $passwordHashService,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
        private SecurityIteratorService $securityIteratorService,
    ) {
    }

    public function checkPassword(User $user, string $password): bool
    {
        $storedHash = $user->getSolidPassword();

        $correct = $this->passwordHashService->checkPassword($storedHash, $password);
        if (!$correct)
        {
            $user->incrementFailedLogin();
            $this->userRepository->save($user);

            return false;
        }

        // Check if password should be migrated
        //
        $migratePassword = $this->passwordHashService->shouldMigrate($storedHash);

        if ($migratePassword)
        {
            $newHash = $this->passwordHashService->generateHash($password);
            $user->setSolidPassword($newHash);
        }

        $user->setLastLogin(time());
        $this->userRepository->save($user);

        return true;
    }

    public function changePassword(User $user, string $oldPassword, string $newPassword): void
    {
        if (strlen($newPassword) < 8)
        {
            throw new WeakPasswordException('The new password is not strong enough');
        }

        // Check if original password is correct
        //
        if ($this->passwordHashService->checkPassword($user->getSolidPassword(), $oldPassword) !== true)
        {
            throw new InvalidPasswordException('The old password does not match the current password');
        }

        // Change password
        //
        $newHash = $this->passwordHashService->generateHash($newPassword);

        $user->setSolidPassword($newHash);
        $this->userRepository->save($user);

        $this->securityIteratorService->incrementFor($user);
    }

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
            $this->configService->get('http_mode').
            '://'.
            $this->container->get('server_name').
            $this->configService->get('base_url').
            $this->configService->get('actions.forgot_password.reset_password_page').
            '?code='.$code;

        return $this->userMailer->passwordReset(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'reset_url' => $resetUrl,
            ]
        );
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

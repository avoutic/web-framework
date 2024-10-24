<?php

namespace WebFramework\Security;

use WebFramework\Entity\User;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Repository\UserRepository;

class ChangePasswordService
{
    public function __construct(
        private AuthenticationService $authenticationService,
        private PasswordHashService $passwordHashService,
        private UserRepository $userRepository,
        private SecurityIteratorService $securityIteratorService,
    ) {}

    public function validate(User $user, string $oldPassword, string $newPassword, string $verificationPassword): void
    {
        if ($newPassword !== $verificationPassword)
        {
            throw new PasswordMismatchException('Passwords don\'t match');
        }

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

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);
    }
}

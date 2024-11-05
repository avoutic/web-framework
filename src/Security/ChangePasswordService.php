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

use WebFramework\Entity\User;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Repository\UserRepository;

/**
 * Class ChangePasswordService.
 *
 * Handles the process of changing a user's password.
 */
class ChangePasswordService
{
    /**
     * ChangePasswordService constructor.
     *
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param PasswordHashService     $passwordHashService     The password hash service
     * @param UserRepository          $userRepository          The user repository
     * @param SecurityIteratorService $securityIteratorService The security iterator service
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private PasswordHashService $passwordHashService,
        private UserRepository $userRepository,
        private SecurityIteratorService $securityIteratorService,
    ) {}

    /**
     * Validate the password change request.
     *
     * @param User   $user                 The user changing their password
     * @param string $oldPassword          The current password
     * @param string $newPassword          The new password
     * @param string $verificationPassword The new password verification
     *
     * @throws PasswordMismatchException If the new password and verification don't match
     * @throws WeakPasswordException     If the new password is too weak
     * @throws InvalidPasswordException  If the old password is incorrect
     */
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

    /**
     * Change the user's password.
     *
     * @param User   $user        The user changing their password
     * @param string $oldPassword The current password
     * @param string $newPassword The new password
     *
     * @throws WeakPasswordException    If the new password is too weak
     * @throws InvalidPasswordException If the old password is incorrect
     */
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

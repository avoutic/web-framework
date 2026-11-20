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
use WebFramework\Event\EventService;
use WebFramework\Event\UserPasswordChanged;
use WebFramework\Exception\AccountDisabledException;
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
     * @param AccountStatusChecker    $accountStatusChecker    The account status checker
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param EventService            $eventService            The event service
     * @param LoggerInterface         $logger                  The logger service
     * @param PasswordHashService     $passwordHashService     The password hash service
     * @param UserRepository          $userRepository          The user repository
     * @param SecurityIteratorService $securityIteratorService The security iterator service
     */
    public function __construct(
        private AccountStatusChecker $accountStatusChecker,
        private AuthenticationService $authenticationService,
        private EventService $eventService,
        private LoggerInterface $logger,
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
     * @throws AccountDisabledException If the account is disabled
     * @throws WeakPasswordException    If the new password is too weak
     * @throws InvalidPasswordException If the old password is incorrect
     */
    public function changePassword(Request $request, User $user, string $oldPassword, string $newPassword): void
    {
        if (strlen($newPassword) < 8)
        {
            throw new WeakPasswordException('The new password is not strong enough');
        }

        // Check if original password is correct
        //
        if ($this->passwordHashService->checkPassword($user->getSolidPassword(), $oldPassword) !== true)
        {
            $this->logger->debug('Invalid current password', ['user_id' => $user->getId()]);

            throw new InvalidPasswordException('The old password does not match the current password');
        }

        // A disabled account does not get its password changed or a fresh session
        //
        if (!$this->accountStatusChecker->isEnabled($user))
        {
            $this->logger->debug('Not changing password for disabled account', ['user_id' => $user->getId()]);

            throw new AccountDisabledException($user);
        }

        // Change password
        //
        $this->logger->info('Changing password', ['user_id' => $user->getId()]);

        $newHash = $this->passwordHashService->generateHash($newPassword);

        $user->setSolidPassword($newHash);
        $this->userRepository->save($user);

        $this->eventService->dispatch(new UserPasswordChanged($request, $user));

        $this->securityIteratorService->incrementFor($user);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);
    }
}

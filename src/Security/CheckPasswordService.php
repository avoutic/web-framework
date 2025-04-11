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

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;

/**
 * Class CheckPasswordService.
 *
 * Handles password checking and related operations.
 */
class CheckPasswordService
{
    /**
     * CheckPasswordService constructor.
     *
     * @param LoggerInterface     $logger              The logger service
     * @param PasswordHashService $passwordHashService The password hash service
     * @param UserRepository      $userRepository      The user repository
     */
    public function __construct(
        private LoggerInterface $logger,
        private PasswordHashService $passwordHashService,
        private UserRepository $userRepository,
    ) {}

    /**
     * Check if the provided password is correct for the given user.
     *
     * @param User   $user     The user to check the password for
     * @param string $password The password to check
     *
     * @return bool True if the password is correct, false otherwise
     */
    public function checkPassword(User $user, string $password): bool
    {
        $storedHash = $user->getSolidPassword();

        $correct = $this->passwordHashService->checkPassword($storedHash, $password);
        if (!$correct)
        {
            $this->logger->debug('Incrementing failed login', ['user_id' => $user->getId()]);

            $user->incrementFailedLogin();
            $this->userRepository->save($user);

            return false;
        }

        // Check if password should be migrated
        //
        $migratePassword = $this->passwordHashService->shouldMigrate($storedHash);

        if ($migratePassword)
        {
            $this->logger->debug('Migrating password', ['user_id' => $user->getId()]);

            $newHash = $this->passwordHashService->generateHash($password);
            $user->setSolidPassword($newHash);
        }

        $user->setLastLogin(Carbon::now()->getTimestamp());
        $this->userRepository->save($user);

        return true;
    }
}

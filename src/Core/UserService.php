<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\PasswordHashService;

/**
 * Class UserService.
 *
 * Handles user-related operations such as creation and availability checks.
 */
class UserService
{
    /**
     * UserService constructor.
     *
     * @param ConfigService       $configService       The configuration service
     * @param LoggerInterface     $logger              The logger
     * @param PasswordHashService $passwordHashService Service for hashing passwords
     * @param UserRepository      $userRepository      Repository for user data operations
     */
    public function __construct(
        private ConfigService $configService,
        private LoggerInterface $logger,
        private PasswordHashService $passwordHashService,
        private UserRepository $userRepository,
    ) {}

    /**
     * Create a new user.
     *
     * @param string $username      The username for the new user
     * @param string $password      The password for the new user
     * @param string $email         The email address for the new user
     * @param int    $termsAccepted Timestamp of when the terms were accepted
     *
     * @return User The newly created User object
     */
    public function createUser(string $username, string $password, string $email, int $termsAccepted): User
    {
        $this->logger->info('Creating user', ['username' => $username, 'email' => $email]);

        $solidPassword = $this->passwordHashService->generateHash($password);

        return $this->userRepository->create([
            'username' => $username,
            'solid_password' => $solidPassword,
            'email' => $email,
            'terms_accepted' => $termsAccepted,
            'registered' => Carbon::now()->getTimestamp(),
        ]);
    }

    /**
     * Check if a username is available.
     *
     * @param string $username The username to check
     *
     * @return bool True if the username is available, false otherwise
     */
    public function isUsernameAvailable(string $username): bool
    {
        $uniqueIdentifier = $this->configService->get('authenticator.unique_identifier');

        // Check if identifier already exists
        //
        if ($uniqueIdentifier == 'email')
        {
            return $this->userRepository->countObjects(['email' => $username]) === 0;
        }

        return $this->userRepository->countObjects(['username' => $username]) === 0;
    }
}

<?php

namespace WebFramework\Security;

use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;

class CheckPasswordService
{
    public function __construct(
        private PasswordHashService $passwordHashService,
        private UserRepository $userRepository,
    ) {}

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
}

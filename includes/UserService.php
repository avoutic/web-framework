<?php

namespace WebFramework\Core;

use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\PasswordHashService;

class UserService
{
    public function __construct(
        private PasswordHashService $passwordHashService,
        private UserRepository $userRepository,
    ) {
    }

    public function createUser(string $username, string $password, string $email, int $termsAccepted): User
    {
        $solidPassword = $this->passwordHashService->generateHash($password);

        return $this->userRepository->create([
            'username' => $username,
            'solid_password' => $solidPassword,
            'email' => $email,
            'terms_accepted' => $termsAccepted,
            'registered' => time(),
        ]);
    }
}

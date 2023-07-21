<?php

namespace WebFramework\Security;

use WebFramework\Core\UserService;
use WebFramework\Entity\User;
use WebFramework\Exception\InvalidCaptchaException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\UsernameUnavailableException;
use WebFramework\Exception\WeakPasswordException;

class RegisterService
{
    public function __construct(
        private UserService $userService,
        private UserVerificationService $userVerificationService,
    ) {
    }

    public function validate(string $username, string $email, string $password, string $passwordVerification, bool $validCaptcha): void
    {
        if (!$validCaptcha)
        {
            throw new InvalidCaptchaException();
        }

        if ($password !== $passwordVerification)
        {
            throw new PasswordMismatchException();
        }

        if (strlen($password) < 8)
        {
            throw new WeakPasswordException();
        }

        if (!$this->userService->isUsernameAvailable($username))
        {
            throw new UsernameUnavailableException();
        }
    }

    /**
     * @param array<mixed> $afterVerifyParams
     */
    public function register(string $username, string $email, string $password, array $afterVerifyParams = []): User
    {
        $user = $this->userService->createUser($username, $password, $email, time());

        $this->userVerificationService->sendVerifyMail($user, $afterVerifyParams);

        return $user;
    }
}

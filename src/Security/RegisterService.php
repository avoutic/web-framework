<?php

namespace WebFramework\Security;

use WebFramework\Core\UserService;
use WebFramework\Entity\User;
use WebFramework\Exception\InvalidCaptchaException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\UsernameUnavailableException;
use WebFramework\Exception\WeakPasswordException;

/**
 * Handles user registration operations.
 */
class RegisterService
{
    /**
     * RegisterService constructor.
     *
     * @param UserService             $userService             The user service
     * @param UserVerificationService $userVerificationService The user verification service
     */
    public function __construct(
        private UserService $userService,
        private UserVerificationService $userVerificationService,
    ) {}

    /**
     * Validate registration data.
     *
     * @param string $username             The username
     * @param string $email                The email address
     * @param string $password             The password
     * @param string $passwordVerification The password verification
     * @param bool   $validCaptcha         Whether a valid CAPTCHA was provided
     *
     * @throws InvalidCaptchaException      If the CAPTCHA is invalid
     * @throws PasswordMismatchException    If the passwords don't match
     * @throws WeakPasswordException        If the password is too weak
     * @throws UsernameUnavailableException If the username is already taken
     */
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
     * Register a new user.
     *
     * @param string       $username          The username
     * @param string       $email             The email address
     * @param string       $password          The password
     * @param array<mixed> $afterVerifyParams Additional parameters for after verification
     *
     * @return User The newly registered user
     */
    public function register(string $username, string $email, string $password, array $afterVerifyParams = []): User
    {
        $user = $this->userService->createUser($username, $password, $email, time());

        $this->userVerificationService->sendVerifyMail($user, $afterVerifyParams);

        return $user;
    }
}

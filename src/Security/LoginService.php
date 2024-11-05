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

use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\CaptchaRequiredException;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Repository\UserRepository;

/**
 * Class LoginService.
 *
 * Handles user login operations.
 */
class LoginService
{
    /**
     * LoginService constructor.
     *
     * @param AuthenticationService $authenticationService The authentication service
     * @param BlacklistService      $blacklistService      The blacklist service
     * @param ConfigService         $configService         The configuration service
     * @param CheckPasswordService  $checkPasswordService  The password checking service
     * @param UserRepository        $userRepository        The user repository
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private BlacklistService $blacklistService,
        private ConfigService $configService,
        private CheckPasswordService $checkPasswordService,
        private UserRepository $userRepository,
    ) {}

    /**
     * Validate login credentials.
     *
     * @param Request $request      The request object
     * @param string  $username     The username or email
     * @param string  $password     The password
     * @param bool    $validCaptcha Whether a valid CAPTCHA was provided
     *
     * @return User The user if validation is successful
     *
     * @throws InvalidPasswordException          If the credentials are invalid
     * @throws CaptchaRequiredException          If a CAPTCHA is required but not provided
     * @throws UserVerificationRequiredException If the user is not verified
     */
    public function validate(Request $request, string $username, string $password, bool $validCaptcha): User
    {
        $user = $this->userRepository->getUserByUsername($username);
        if ($user === null)
        {
            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'unknown-username');

            throw new InvalidPasswordException();
        }

        if (!$validCaptcha && $this->captchaRequired($user))
        {
            throw new CaptchaRequiredException();
        }

        if (!$this->checkPasswordService->checkPassword($user, $password))
        {
            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'wrong-password');

            throw new InvalidPasswordException();
        }

        if (!$user->isVerified())
        {
            throw new UserVerificationRequiredException($user);
        }

        return $user;
    }

    /**
     * Authenticate a user.
     *
     * @param User   $user     The user to authenticate
     * @param string $password The password to verify
     *
     * @throws InvalidPasswordException          If the password is incorrect
     * @throws UserVerificationRequiredException If the user is not verified
     */
    public function authenticate(User $user, string $password): void
    {
        if (!$this->checkPasswordService->checkPassword($user, $password))
        {
            throw new InvalidPasswordException();
        }

        if (!$user->isVerified())
        {
            throw new UserVerificationRequiredException($user);
        }

        $this->authenticationService->authenticate($user);
    }

    /**
     * Check if a CAPTCHA is required for the given user.
     *
     * @param User $user The user to check
     *
     * @return bool True if a CAPTCHA is required, false otherwise
     */
    public function captchaRequired(User $user): bool
    {
        $bruteforceProtection = $this->configService->get('actions.login.bruteforce_protection');

        if ($user->getFailedLogin() > 5 && $bruteforceProtection)
        {
            return true;
        }

        return false;
    }
}

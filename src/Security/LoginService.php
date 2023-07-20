<?php

namespace WebFramework\Security;

use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\CaptchaRequiredException;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Repository\UserRepository;

class LoginService
{
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected BlacklistService $blacklistService,
        protected ConfigService $configService,
        protected UserPasswordService $userPasswordService,
        protected UserRepository $userRepository,
    ) {
    }

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

        if (!$this->userPasswordService->checkPassword($user, $password))
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

    public function authenticate(User $user, string $password): void
    {
        if (!$this->userPasswordService->checkPassword($user, $password))
        {
            throw new InvalidPasswordException();
        }

        if (!$user->isVerified())
        {
            throw new UserVerificationRequiredException($user);
        }

        $this->authenticationService->authenticate($user);
    }

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

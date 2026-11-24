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
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserLoggedIn;
use WebFramework\Exception\AccountDisabledException;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\LoginRefusedException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\Extension\LoginExtensionInterface;

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
     * @param AccountStatusChecker    $accountStatusChecker  The account status checker
     * @param AuthenticationService   $authenticationService The authentication service
     * @param BlacklistService        $blacklistService      The blacklist service
     * @param CheckPasswordService    $checkPasswordService  The password checking service
     * @param EventService            $eventService          The event service
     * @param LoggerInterface         $logger                The logger service
     * @param LoginExtensionInterface $loginExtension        The login extension
     * @param UserRepository          $userRepository        The user repository
     * @param int                     $validityPeriodDays    The number of days that email verification remains valid
     */
    public function __construct(
        private AccountStatusChecker $accountStatusChecker,
        private AuthenticationService $authenticationService,
        private BlacklistService $blacklistService,
        private CheckPasswordService $checkPasswordService,
        private EventService $eventService,
        private LoggerInterface $logger,
        private LoginExtensionInterface $loginExtension,
        private UserRepository $userRepository,
        private int $validityPeriodDays,
    ) {}

    /**
     * Validate login credentials.
     *
     * This only checks the credentials and the account status. Whether the user is
     * verified is checked in authenticate(), so callers can run their own checks
     * (such as LoginExtensionInterface::customValueCheck()) before a verification
     * code is sent.
     *
     * @param Request $request  The request object
     * @param string  $username The username or email
     * @param string  $password The password
     *
     * @return User The user if validation is successful
     *
     * @throws AccountDisabledException If the account is disabled
     * @throws InvalidPasswordException If the credentials are invalid
     */
    public function validate(Request $request, string $username, string $password): User
    {
        $user = $this->userRepository->getUserByUsername($username);
        if ($user === null)
        {
            $this->logger->debug('Unknown username', ['username' => $username]);

            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'unknown-username');

            throw new InvalidPasswordException();
        }

        if (!$this->checkPasswordService->checkPassword($user, $password))
        {
            $this->logger->debug('Wrong password', ['user_id' => $user->getId()]);

            $this->blacklistService->addEntry($request->getAttribute('ip'), null, 'wrong-password');

            throw new InvalidPasswordException();
        }

        // Here already, so a disabled account is never sent a verification code
        //
        $this->requireEnabledAccount($user);

        return $user;
    }

    /**
     * Authenticate a user whose credentials have been checked with validate().
     *
     * This does not check the password. It checks that the user is verified and then
     * completes the login.
     *
     * @param Request $request The current request
     * @param User    $user    The user to authenticate
     *
     * @throws AccountDisabledException          If the account is disabled
     * @throws LoginRefusedException             If the login extension refuses the login
     * @throws UserVerificationRequiredException If the user is not verified
     */
    public function authenticate(Request $request, User $user): void
    {
        if (!$user->isVerified())
        {
            $this->logger->debug('User not verified', ['user_id' => $user->getId()]);

            throw new UserVerificationRequiredException($user);
        }

        if ($this->isVerificationExpired($request, $user))
        {
            $this->logger->debug('User verification expired', ['user_id' => $user->getId()]);

            throw new UserVerificationRequiredException($user);
        }

        $this->completeLogin($request, $user);
    }

    /**
     * Complete the login of a user that has passed all credential and verification checks.
     *
     * This is the single place where a login results in a session. It is used by
     * password login, login verification and registration verification.
     *
     * @param Request $request The current request
     * @param User    $user    The user to log in
     *
     * @throws AccountDisabledException If the account is disabled
     * @throws LoginRefusedException    If the login extension refuses the login
     */
    public function completeLogin(Request $request, User $user): void
    {
        $this->requireEnabledAccount($user);

        if (!$this->loginExtension->preAuthenticate($request, $user))
        {
            $this->logger->debug('Login refused by extension', ['user_id' => $user->getId()]);

            throw new LoginRefusedException($user);
        }

        $this->authenticationService->authenticate($user);

        // Record the login here, where the session is created, so refused logins
        // do not count as account activity. This also resets the failed login counter
        //
        $user->setLastLogin(Carbon::now()->getTimestamp());
        $this->userRepository->save($user);

        $this->eventService->dispatch(new UserLoggedIn($request, $user));
    }

    /**
     * Check if a verified user has to verify again, because the verification is too old.
     */
    private function isVerificationExpired(Request $request, User $user): bool
    {
        // A validity period of 0 means the verification does not expire
        //
        if ($this->validityPeriodDays <= 0)
        {
            return false;
        }

        if ($user->isVerifiedValid($this->validityPeriodDays))
        {
            return false;
        }

        return !$this->loginExtension->isExemptFromVerificationExpiry($request, $user);
    }

    /**
     * @throws AccountDisabledException If the account is disabled
     */
    private function requireEnabledAccount(User $user): void
    {
        if (!$this->accountStatusChecker->isEnabled($user))
        {
            $this->logger->debug('Account disabled', ['user_id' => $user->getId()]);

            throw new AccountDisabledException($user);
        }
    }
}

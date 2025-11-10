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
use WebFramework\Core\UserService;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserRegistered;
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
     * @param EventService            $eventService            The event service
     * @param LoggerInterface         $logger                  The logger service
     * @param UserService             $userService             The user service
     * @param UserVerificationService $userVerificationService The user verification service
     */
    public function __construct(
        private EventService $eventService,
        private LoggerInterface $logger,
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
     *
     * @throws PasswordMismatchException    If the passwords don't match
     * @throws WeakPasswordException        If the password is too weak
     * @throws UsernameUnavailableException If the username is already taken
     */
    public function validate(string $username, string $email, string $password, string $passwordVerification): void
    {
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
     * @return array{user: User, guid: string} The newly registered user and the verification GUID
     */
    public function register(Request $request, string $username, string $email, string $password, array $afterVerifyParams = []): array
    {
        $this->logger->info('Registering new user', ['username' => $username, 'email' => $email]);

        $user = $this->userService->createUser($username, $password, $email, Carbon::now()->getTimestamp());

        $guid = $this->userVerificationService->sendVerifyMail($user, 'register', $afterVerifyParams);

        $this->eventService->dispatch(new UserRegistered($request, $user));

        return ['user' => $user, 'guid' => $guid];
    }
}

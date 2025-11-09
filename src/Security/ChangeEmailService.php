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

use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserEmailChanged;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\WrongAccountException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;

/**
 * Class ChangeEmailService.
 *
 * Handles the process of changing a user's email address.
 */
class ChangeEmailService
{
    /**
     * ChangeEmailService constructor.
     *
     * @param AuthenticationService      $authenticationService      The authentication service
     * @param EventService               $eventService               The event service
     * @param LoggerInterface            $logger                     The logger service
     * @param SecurityIteratorService    $securityIteratorService    The security iterator service
     * @param UserCodeService            $userCodeService            The user code service
     * @param UserMailer                 $userMailer                 The user mailer service
     * @param UserRepository             $userRepository             The user repository
     * @param VerificationCodeRepository $verificationCodeRepository The verification code repository
     * @param int                        $codeExpiryMinutes          The number of minutes until verification codes expire
     * @param string                     $uniqueIdentifier           The unique identifier type ('email' or 'username')
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private EventService $eventService,
        private LoggerInterface $logger,
        private SecurityIteratorService $securityIteratorService,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
        private VerificationCodeRepository $verificationCodeRepository,
        private int $codeExpiryMinutes,
        private string $uniqueIdentifier,
    ) {}

    /**
     * Change the email address for a user.
     *
     * @param Request $request The request that triggered the event
     * @param User    $user    The user whose email is being changed
     * @param string  $email   The new email address
     *
     * @throws DuplicateEmailException If the email already exists and is the unique identifier
     */
    public function changeEmail(Request $request, User $user, string $email): void
    {
        if ($this->uniqueIdentifier == 'email')
        {
            $count = $this->userRepository->countObjects(['email' => $email]);

            if ($count > 0)
            {
                $this->logger->debug('E-mail address already exists', ['email' => $email]);

                throw new DuplicateEmailException('E-mail address already exists');
            }
        }

        $this->logger->info('Changing email address', ['user_id' => $user->getId(), 'email' => $email]);

        // Update account
        //
        $user->setEmail($email);

        if ($this->uniqueIdentifier == 'email')
        {
            $this->logger->info('Setting username to email', ['user_id' => $user->getId(), 'email' => $email]);

            $user->setUsername($email);
        }

        $this->userRepository->save($user);

        $this->eventService->dispatch(new UserEmailChanged($request, $user));
    }

    /**
     * Send a verification email for changing the email address.
     *
     * @param User   $user  The user requesting the email change
     * @param string $email The new email address
     *
     * @return string The GUID for passing to the verify action
     *
     * @throws DuplicateEmailException If the email already exists and is the unique identifier
     */
    public function sendChangeEmailVerify(User $user, string $email): string
    {
        if ($this->uniqueIdentifier == 'email')
        {
            $count = $this->userRepository->countObjects(['email' => $email]);

            if ($count > 0)
            {
                $this->logger->debug('E-mail address already exists', ['email' => $email]);

                throw new DuplicateEmailException('E-mail address already exists');
            }
        }

        $securityIterator = $this->securityIteratorService->incrementFor($user);

        ['guid' => $guid, 'code' => $code] = $this->userCodeService->generateVerificationCodeEntry(
            $user,
            'change_email',
            [
                'email' => $email,
                'iterator' => $securityIterator,
            ]
        );

        $this->logger->debug('Sending change email verification link', ['user_id' => $user->getId(), 'email' => $email]);

        $this->userMailer->changeEmailVerificationCode(
            $email,
            [
                'user' => $user->toArray(),
                'code' => $code,
                'validity' => $this->codeExpiryMinutes,
            ]
        );

        return $guid;
    }

    /**
     * Handle the data for an email change request.
     *
     * @param Request $request The request that triggered the event
     * @param User    $user    The user verifying the email change
     * @param string  $guid    The GUID of the verification code (already verified)
     *
     * @throws CodeVerificationException If the data is invalid
     * @throws WrongAccountException     If the code doesn't match the current user
     */
    public function handleData(Request $request, User $user, string $guid): void
    {
        $this->logger->debug('Handling change email data', ['guid' => $guid]);

        $verificationCode = $this->verificationCodeRepository->getByGuid($guid);

        if ($verificationCode === null)
        {
            throw new CodeVerificationException();
        }

        // Verify the code was actually verified (marked as used) and hasn't expired
        if (!$verificationCode->isCorrect())
        {
            $this->logger->debug('Verification code not yet correct', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->isExpired())
        {
            $this->logger->debug('Verification code expired', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        // Prevent replay attacks - check if code has already been invalidated or processed
        if ($verificationCode->isInvalidated() || $verificationCode->isProcessed())
        {
            $this->logger->warning('Verification code already invalidated or processed (replay attempt)', ['guid' => $guid]);

            throw new CodeVerificationException();
        }

        if ($verificationCode->getAction() !== 'change_email')
        {
            $this->logger->error('Invalid action for verification code', [
                'guid' => $guid,
                'action' => $verificationCode->getAction(),
            ]);

            throw new CodeVerificationException();
        }

        $codeUser = $this->userRepository->getObjectById($verificationCode->getUserId());
        if ($codeUser === null)
        {
            throw new CodeVerificationException();
        }

        // Only allow for current user
        //
        if ($codeUser->getId() !== $user->getId())
        {
            $this->logger->debug('Received change email verify for wrong account', ['user_id' => $user->getId(), 'code_user_id' => $codeUser->getId()]);

            $this->authenticationService->deauthenticate();

            throw new WrongAccountException();
        }

        $flowData = $verificationCode->getFlowData();
        $email = $flowData['email'] ?? '';

        // Already changed
        //
        if ($user->getEmail() === $email)
        {
            $this->logger->debug('Already changed email address', ['user_id' => $user->getId(), 'email' => $email]);

            return;
        }

        // Change email
        //
        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($flowData['iterator']) || $securityIterator != $flowData['iterator'])
        {
            $this->logger->debug('Change email verification has old iterator', ['user_id' => $user->getId(), 'email' => $email]);

            throw new CodeVerificationException();
        }

        // Mark as processed to prevent replay attacks
        $verificationCode->markAsProcessed();
        $this->verificationCodeRepository->save($verificationCode);

        $this->changeEmail($request, $user, $email);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);
    }
}

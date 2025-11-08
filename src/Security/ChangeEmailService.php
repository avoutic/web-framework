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
use WebFramework\Support\UrlBuilder;

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
     * @param AuthenticationService   $authenticationService   The authentication service
     * @param EventService            $eventService            The event service
     * @param LoggerInterface         $logger                  The logger service
     * @param SecurityIteratorService $securityIteratorService The security iterator service
     * @param UrlBuilder              $urlBuilder              The URL builder service
     * @param UserCodeService         $userCodeService         The user code service
     * @param UserMailer              $userMailer              The user mailer service
     * @param UserRepository          $userRepository          The user repository
     * @param string                  $uniqueIdentifier        The unique identifier type ('email' or 'username')
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private EventService $eventService,
        private LoggerInterface $logger,
        private SecurityIteratorService $securityIteratorService,
        private UrlBuilder $urlBuilder,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
        private string $uniqueIdentifier,
    ) {}

    /**
     * Change the email address for a user.
     *
     * @param Request $request       The request that triggered the event
     * @param User    $user          The user whose email is being changed
     * @param string  $email         The new email address
     * @param bool    $requireUnique Whether to require the new email to be unique
     *
     * @throws DuplicateEmailException If the email already exists and $requireUnique is true
     */
    public function changeEmail(Request $request, User $user, string $email, bool $requireUnique = true): void
    {
        if ($requireUnique)
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
     * @param User   $user          The user requesting the email change
     * @param string $email         The new email address
     * @param bool   $requireUnique Whether to require the new email to be unique
     *
     * @throws DuplicateEmailException If the email already exists and $requireUnique is true
     */
    public function sendChangeEmailVerify(User $user, string $email, bool $requireUnique = true): void
    {
        if ($requireUnique)
        {
            $count = $this->userRepository->countObjects(['email' => $email]);

            if ($count > 0)
            {
                $this->logger->debug('E-mail address already exists', ['email' => $email]);

                throw new DuplicateEmailException('E-mail address already exists');
            }
        }

        $securityIterator = $this->securityIteratorService->incrementFor($user);

        $code = $this->userCodeService->generate($user, 'change_email', ['email' => $email, 'iterator' => $securityIterator]);

        $verifyUrl =
            $this->urlBuilder->getServerUrl().
            $this->urlBuilder->buildQueryUrl(
                $this->configService->get('actions.change_email.verify_page'),
                [],
                ['code' => $code],
            );

        $this->logger->debug('Sending change email verification link', ['user_id' => $user->getId(), 'email' => $email]);

        $this->userMailer->changeEmailVerificationLink(
            $email,
            [
                'user' => $user->toArray(),
                'verify_url' => $verifyUrl,
            ]
        );
    }

    /**
     * Verify the code for an email change request.
     *
     * @param Request $request The request that triggered the event
     * @param User    $user    The user verifying the email change
     * @param string  $code    The verification code
     *
     * @throws CodeVerificationException If the verification code is invalid
     * @throws WrongAccountException     If the code doesn't match the current user
     */
    public function verifyLinkCode(Request $request, User $user, string $code): void
    {
        ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
            $code,
            validity: 10 * 60,
            action: 'change_email',
        );

        // Check user status
        //
        $codeUser = $this->userRepository->getObjectById($codeUserId);
        if ($codeUser === null)
        {
            throw new CodeVerificationException();
        }

        $email = $verifyParams['email'];

        // Only allow for current user
        //
        if ($codeUser->getId() !== $user->getId())
        {
            $this->logger->debug('Received change email verify for wrong account', ['user_id' => $user->getId(), 'code_user_id' => $codeUser->getId()]);

            $this->authenticationService->deauthenticate();

            throw new WrongAccountException();
        }

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

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
            $this->logger->debug('Change email verification has old iterator', ['user_id' => $user->getId(), 'email' => $email]);

            throw new CodeVerificationException();
        }

        $this->changeEmail($request, $user, $email);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);
    }
}

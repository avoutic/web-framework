<?php

namespace WebFramework\Security;

use WebFramework\Core\ConfigService;
use WebFramework\Core\UrlBuilder;
use WebFramework\Core\UserMailer;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\WrongAccountException;
use WebFramework\Repository\UserRepository;

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
     * @param ConfigService           $configService           The configuration service
     * @param SecurityIteratorService $securityIteratorService The security iterator service
     * @param UrlBuilder              $urlBuilder              The URL builder service
     * @param UserCodeService         $userCodeService         The user code service
     * @param UserMailer              $userMailer              The user mailer service
     * @param UserRepository          $userRepository          The user repository
     */
    public function __construct(
        private AuthenticationService $authenticationService,
        private ConfigService $configService,
        private SecurityIteratorService $securityIteratorService,
        private UrlBuilder $urlBuilder,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
    ) {}

    /**
     * Change the email address for a user.
     *
     * @param User   $user          The user whose email is being changed
     * @param string $email         The new email address
     * @param bool   $requireUnique Whether to require the new email to be unique
     *
     * @throws DuplicateEmailException If the email already exists and $requireUnique is true
     */
    public function changeEmail(User $user, string $email, bool $requireUnique = true): void
    {
        if ($requireUnique)
        {
            $count = $this->userRepository->countObjects(['email' => $email]);

            if ($count > 0)
            {
                throw new DuplicateEmailException('E-mail address already exists');
            }
        }

        // Update account
        //
        $user->setEmail($email);

        if ($this->configService->get('authenticator.unique_identifier') == 'email')
        {
            $user->setUsername($email);
        }

        $this->userRepository->save($user);
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

        $this->userMailer->changeEmailVerificationLink(
            $email,
            [
                'user' => $user->toArray(),
                'verify_url' => $verifyUrl,
            ]
        );
    }

    /**
     * Handle the verification of an email change request.
     *
     * @param User   $user The user verifying the email change
     * @param string $code The verification code
     *
     * @throws CodeVerificationException If the verification code is invalid
     * @throws WrongAccountException     If the code doesn't match the current user
     */
    public function handleChangeEmailVerify(User $user, string $code): void
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
            $this->authenticationService->deauthenticate();

            throw new WrongAccountException();
        }

        // Already changed
        //
        if ($user->getEmail() === $email)
        {
            return;
        }

        // Change email
        //
        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($verifyParams['iterator']) || $securityIterator != $verifyParams['iterator'])
        {
            throw new CodeVerificationException();
        }

        $this->changeEmail($user, $email);

        // Invalidate old sessions
        //
        $this->authenticationService->invalidateSessions($user->getId());
        $this->authenticationService->authenticate($user);
    }
}

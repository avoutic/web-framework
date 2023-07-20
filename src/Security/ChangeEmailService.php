<?php

namespace WebFramework\Security;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Core\ConfigService;
use WebFramework\Core\UserMailer;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\WrongAccountException;
use WebFramework\Repository\UserRepository;

class ChangeEmailService
{
    public function __construct(
        private Container $container,
        private AuthenticationService $authenticationService,
        private ConfigService $configService,
        private SecurityIteratorService $securityIteratorService,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
    ) {
    }

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
            $this->configService->get('http_mode').
            '://'.
            $this->container->get('server_name').
            $this->configService->get('base_url').
            $this->configService->get('actions.change_email.verify_page').
            '?code='.$code;

        $this->userMailer->changeEmailVerificationLink(
            $email,
            [
                'user' => $user->toArray(),
                'verify_url' => $verifyUrl,
            ]
        );
    }

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

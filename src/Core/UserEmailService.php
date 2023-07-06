<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Entity\User;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\SecurityIteratorService;

class UserEmailService
{
    public function __construct(
        private Container $container,
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

    /**
     * @param array<mixed> $afterVerifyData
     */
    public function sendVerifyMail(User $user, array $afterVerifyData = []): void
    {
        $code = $this->userCodeService->generate($user, 'verify', $afterVerifyData);
        $verifyUrl =
            $this->configService->get('http_mode').
            '://'.
            $this->container->get('server_name').
            $this->configService->get('base_url').
            $this->configService->get('actions.login.verify_page').
            '?code='.$code;

        $this->userMailer->emailVerificationLink(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'verify_url' => $verifyUrl,
            ]
        );
    }
}

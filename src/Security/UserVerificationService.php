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
use WebFramework\Core\ConfigService;
use WebFramework\Core\UserMailer;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserVerified;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;
use WebFramework\Support\UrlBuilder;

/**
 * Handles user verification processes.
 */
class UserVerificationService
{
    /**
     * UserVerificationService constructor.
     *
     * @param ConfigService   $configService   The configuration service
     * @param EventService    $eventService    The event service
     * @param LoggerInterface $logger          The logger service
     * @param UrlBuilder      $urlBuilder      The URL builder service
     * @param UserCodeService $userCodeService The user code service
     * @param UserMailer      $userMailer      The user mailer service
     * @param UserRepository  $userRepository  The user repository
     */
    public function __construct(
        private ConfigService $configService,
        private EventService $eventService,
        private LoggerInterface $logger,
        private UrlBuilder $urlBuilder,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
    ) {}

    /**
     * Handle sending a verification email.
     *
     * @param string $code The verification code
     *
     * @throws CodeVerificationException If the code is invalid
     */
    public function handleSendVerify(string $code): void
    {
        $this->logger->debug('Sending verification mail', ['code' => $code]);

        ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
            $code,
            validity: 24 * 60 * 60,
            action: 'send_verify',
        );

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user === null)
        {
            $this->logger->debug('User not found', ['code_user_id' => $codeUserId]);

            throw new CodeVerificationException();
        }

        if (!$user->isVerified())
        {
            $this->sendVerifyMail($user, $verifyParams);
        }
    }

    /**
     * Send a verification email to a user.
     *
     * @param User         $user            The user to send the verification email to
     * @param array<mixed> $afterVerifyData Additional data to include after verification
     */
    public function sendVerifyMail(User $user, array $afterVerifyData = []): void
    {
        $this->logger->debug('Sending verification mail', ['user_id' => $user->getId()]);

        $code = $this->userCodeService->generate($user, 'verify', $afterVerifyData);

        $verifyUrl =
            $this->urlBuilder->getServerUrl().
            $this->urlBuilder->buildQueryUrl(
                $this->configService->get('actions.login.verify_page'),
                [],
                ['code' => $code],
            );

        $this->userMailer->emailVerificationLink(
            $user->getEmail(),
            [
                'user' => $user->toArray(),
                'verify_url' => $verifyUrl,
            ]
        );
    }

    /**
     * Handle user verification.
     *
     * @param string $code The verification code
     *
     * @throws CodeVerificationException If the code is invalid
     */
    public function handleVerify(string $code): void
    {
        $this->logger->debug('Handling code verification', ['code' => $code]);

        ['user_id' => $codeUserId, 'params' => $verifyParams] = $this->userCodeService->verify(
            $code,
            validity: 24 * 60 * 60,
            action: 'verify',
        );

        // Check user status
        //
        $user = $this->userRepository->getObjectById($codeUserId);
        if ($user === null)
        {
            $this->logger->debug('User not found', ['code_user_id' => $codeUserId]);

            throw new CodeVerificationException();
        }

        if (!$user->isVerified())
        {
            $this->logger->info('Setting user to verified', ['user_id' => $user->getId()]);

            $user->setVerified();
            $this->userRepository->save($user);

            $this->eventService->dispatch(new UserVerified($user));
        }
    }
}

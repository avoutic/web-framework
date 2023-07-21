<?php

namespace WebFramework\Security;

use WebFramework\Core\ConfigService;
use WebFramework\Core\UrlBuilder;
use WebFramework\Core\UserMailer;
use WebFramework\Entity\User;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Repository\UserRepository;

class UserVerificationService
{
    public function __construct(
        private ConfigService $configService,
        private UrlBuilder $urlBuilder,
        private UserCodeService $userCodeService,
        private UserMailer $userMailer,
        private UserRepository $userRepository,
    ) {
    }

    public function handleSendVerify(string $code): void
    {
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
            throw new CodeVerificationException();
        }

        if (!$user->isVerified())
        {
            $this->sendVerifyMail($user, $verifyParams);
        }
    }

    /**
     * @param array<mixed> $afterVerifyData
     */
    public function sendVerifyMail(User $user, array $afterVerifyData = []): void
    {
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

    public function handleVerify(string $code): void
    {
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
            throw new CodeVerificationException();
        }

        if (!$user->isVerified())
        {
            $user->setVerified();
            $this->userRepository->save($user);
        }
    }
}

<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\UserPasswordService;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\SecurityIteratorService;

class ResetPassword extends PageAction
{
    protected SecurityIteratorService $securityIteratorService;
    protected UserRepository $userRepository;
    protected UserPasswordService $userPasswordService;

    public function init(): void
    {
        parent::init();

        $this->securityIteratorService = $this->container->get(SecurityIteratorService::class);
        $this->userPasswordService = $this->container->get(UserPasswordService::class);
        $this->userRepository = $this->container->get(UserRepository::class);
    }

    public static function getFilter(): array
    {
        return [
            'code' => '.*',
        ];
    }

    protected function getTitle(): string
    {
        return 'Reset password';
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function getUser(string $username): ?User
    {
        return $this->userRepository->getUserByUsername($username);
    }

    protected function doLogic(): void
    {
        $forgotPasswordPage = $this->getBaseUrl().$this->getConfig('actions.forgot_password.location');
        $loginPage = $this->getBaseUrl().$this->getConfig('actions.login.location');

        // Check if code is present
        //
        $code = $this->getInputVar('code');
        $this->blacklistVerify(strlen($code), 'missing-code');

        $msg = $this->decodeAndVerifyArray($code);
        if (!$msg)
        {
            return;
        }

        $this->blacklistVerify($msg['action'] == 'reset_password', 'wrong-action', 2);

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: {$forgotPasswordPage}?".$this->getMessageForUrl('error', 'Password reset link expired'));

            exit();
        }

        $user = $this->getUser($msg['username']);

        if ($user === null)
        {
            return;
        }

        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($msg['params']) || !isset($msg['params']['iterator'])
            || $securityIterator != $msg['params']['iterator'])
        {
            header("Location: {$forgotPasswordPage}?".$this->getMessageForUrl('error', 'Password reset link expired'));

            exit();
        }

        $this->userPasswordService->sendNewPassword($user);

        // Invalidate old sessions
        //
        $this->invalidateSessions($user->getId());

        // Redirect to main sceen
        //
        header("Location: {$loginPage}?".$this->getMessageForUrl('success', 'Password reset', 'You will receive a mail with your new password'));

        exit();
    }
}

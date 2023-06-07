<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\UserEmailService;
use WebFramework\Repository\UserRepository;

class SendVerify extends PageAction
{
    protected UserEmailService $userEmailService;
    protected UserRepository $userRepository;

    public function init(): void
    {
        parent::init();

        $this->userEmailService = $this->container->get(UserEmailService::class);
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
        return 'Request verification mail.';
    }

    protected function doLogic(): void
    {
        // Check if code is present
        //
        $code = $this->getInputVar('code');
        $this->blacklistVerify(strlen($code), 'missing-code');

        $msg = $this->decodeAndVerifyArray($code);
        if (!$msg)
        {
            exit();
        }

        $this->blacklistVerify($msg['action'] == 'send_verify', 'wrong-action', 2);

        if ($msg['timestamp'] + 86400 < time())
        {
            $loginPage = $this->getBaseUrl().$this->getConfig('actions.login.location');

            // Expired
            header("Location: {$loginPage}?".$this->getMessageForUrl('error', 'Send verification link expired', 'Please login again to request a new one.'));

            exit();
        }

        // Check user status
        //
        $user = $this->userRepository->getUserByUsername($msg['username']);

        if ($user !== null && !$user->isVerified())
        {
            $this->userEmailService->sendVerifyMail($user, $msg['params']);
        }

        // Redirect to main sceen
        //
        $afterVerifyPage = $this->getBaseUrl().$this->getConfig('actions.send_verify.after_verify_page');

        header("Location: {$afterVerifyPage}?".$this->getMessageForUrl('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.'));

        exit();
    }

    protected function displayContent(): void
    {
    }
}

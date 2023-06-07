<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;

class Verify extends PageAction
{
    protected UserRepository $userRepository;

    public function init(): void
    {
        parent::init();

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
        return 'Mail address verification';
    }

    /**
     * @param array<mixed> $data
     */
    protected function customAfterVerifyActions(User $user, array $data): void
    {
    }

    protected function doLogic(): void
    {
        $loginPage = $this->getBaseUrl().$this->getConfig('actions.login.location');

        // Check if code is present
        //
        $code = $this->getInputVar('code');
        $this->blacklistVerify(strlen($code), 'missing-code');

        $msg = $this->decodeAndVerifyArray($code);
        if (!$msg)
        {
            header("Location: {$loginPage}?".$this->getMessageForUrl('error', 'Verification mail expired', 'Please <a href="'.$loginPage.'">request a new one</a> after logging in.'));

            exit();
        }

        $this->blacklistVerify($msg['action'] == 'verify', 'wrong-action', 2);

        if ($msg['timestamp'] + 86400 < time())
        {
            // Expired
            header("Location: {$loginPage}?".$this->getMessageForUrl('error', 'Verification mail expired', 'Please <a href="'.$loginPage.'">request a new one</a> after logging in.'));

            exit();
        }

        // Check user status
        //
        $user = $this->userRepository->getUserByUsername($msg['username']);

        if ($user === null)
        {
            return;
        }

        if (!$user->isVerified())
        {
            $user->setVerified();
            $this->userRepository->save($user);

            $this->customAfterVerifyActions($user, $msg['params']);
        }

        // Redirect to main sceen
        //
        $afterVerifyPage = $this->getConfig('actions.login.after_verify_page');
        header("Location: {$loginPage}?".$this->getMessageForUrl('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.').'&return_page='.urlencode($afterVerifyPage));

        exit();
    }
}

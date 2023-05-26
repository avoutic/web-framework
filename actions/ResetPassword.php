<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ResetPassword extends PageAction
{
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
    protected function getUser(string $username): User|false
    {
        $factory = $this->container->get(BaseFactory::class);

        return $factory->getUserByUsername($username);
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

        if ($user === false)
        {
            return;
        }

        if (!isset($msg['params']) || !isset($msg['params']['iterator'])
            || $user->getSecurityIterator() != $msg['params']['iterator'])
        {
            header("Location: {$forgotPasswordPage}?".$this->getMessageForUrl('error', 'Password reset link expired'));

            exit();
        }

        $user->sendNewPassword();

        // Invalidate old sessions
        //
        $this->invalidateSessions($user->id);

        // Redirect to main sceen
        //
        header("Location: {$loginPage}?".$this->getMessageForUrl('success', 'Password reset', 'You will receive a mail with your new password'));

        exit();
    }
}

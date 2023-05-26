<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ChangeEmailVerify extends PageAction
{
    public static function getFilter(): array
    {
        return [
            'code' => '.*',
        ];
    }

    public static function getPermissions(): array
    {
        return [
            'logged_in',
        ];
    }

    protected function getTitle(): string
    {
        return 'Change email address verification';
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
        $changePage = $this->getBaseUrl().$this->getConfig('actions.change_email.location');

        // Check if code is present
        //
        $code = $this->getInputVar('code');
        $this->blacklistVerify(strlen($code), 'code-missing');

        $msg = $this->decodeAndVerifyArray($code);
        if (!$msg)
        {
            exit();
        }

        $this->blacklistVerify($msg['action'] == 'change_email', 'wrong-action', 2);

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: {$changePage}?".$this->getMessageForUrl('error', 'E-mail verification link expired'));

            exit();
        }

        $userId = $msg['id'];
        $email = $msg['params']['email'];
        $this->pageContent['email'] = $email;

        // Only allow for current user
        //
        $user = $this->getAuthenticatedUser();
        if ($userId != $user->id)
        {
            $this->deauthenticate();
            $loginPage = $this->getBaseUrl().$this->getConfig('actions.login.location');
            header("Location: {$loginPage}?".$this->getMessageForUrl('error', 'Other account', 'The link you used is meant for a different account. The current account has been logged off. Please try the link again.'));

            exit();
        }

        // Change email
        //
        $oldEmail = $user->email;

        if (!isset($msg['params']) || !isset($msg['params']['iterator'])
            || $user->getSecurityIterator() != $msg['params']['iterator'])
        {
            header("Location: {$changePage}?".$this->getMessageForUrl('error', 'E-mail verification link expired'));

            exit();
        }

        $result = $user->changeEmail($email);

        if ($result == User::ERR_DUPLICATE_EMAIL)
        {
            header("Location: {$changePage}?".$this->getMessageForUrl('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.'));

            exit();
        }
        $this->verify($result == User::RESULT_SUCCESS, 'Unknown change email error');

        // Invalidate old sessions
        //
        $this->invalidateSessions($user->id);
        $this->authenticate($user);

        // Redirect to verification request screen
        //
        $returnPage = $this->getBaseUrl().$this->getConfig('actions.change_email.return_page');
        header("Location: {$returnPage}?".$this->getMessageForUrl('success', 'E-mail address changed successfully'));

        exit();
    }
}

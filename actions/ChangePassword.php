<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ChangePassword extends PageAction
{
    public static function getFilter(): array
    {
        return [
            'orig_password' => FORMAT_PASSWORD,
            'password' => FORMAT_PASSWORD,
            'password2' => FORMAT_PASSWORD,
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
        return 'Change password';
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function getUser(string $username): User|false
    {
        $factory = $this->container->get(BaseFactory::class);

        return $factory->getUserByUsername($username);
    }

    protected function customFinalizeChange(): void
    {
    }

    protected function doLogic(): void
    {
        // Check if this is a true attempt
        //
        if (!strlen($this->getInputVar('do')))
        {
            return;
        }

        $origPassword = $this->getInputVar('orig_password');
        $password = $this->getInputVar('password');
        $password2 = $this->getInputVar('password2');

        // Check if passwords are present
        //
        if (!strlen($origPassword))
        {
            $this->addMessage('error', 'Please enter your current password.');

            return;
        }

        if (!strlen($password))
        {
            $this->addMessage('error', 'Please enter a password.', 'Passwords can contain any printable character.');

            return;
        }

        if (!strlen($password2))
        {
            $this->addMessage('error', 'Please enter the password verification.', 'Password verification should match your password.');

            return;
        }

        if ($password != $password2)
        {
            $this->addMessage('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');

            return;
        }

        $user = $this->getAuthenticatedUser();

        $result = $user->changePassword($origPassword, $password);

        if ($result == User::ERR_ORIG_PASSWORD_MISMATCH)
        {
            $this->addMessage('error', 'Original password is incorrect.', 'Please re-enter your password.');

            return;
        }

        if ($result == User::ERR_NEW_PASSWORD_TOO_WEAK)
        {
            $this->addMessage('error', 'New password is too weak.', 'Use at least 8 characters.');

            return;
        }

        if ($result != User::RESULT_SUCCESS)
        {
            $this->addMessage('error', 'Unknown errorcode: \''.$result."'", 'Please inform the administrator.');

            return;
        }

        $this->customFinalizeChange();

        // Invalidate old sessions
        //
        $this->invalidateSessions($user->id);
        $this->authenticate($user);

        // Redirect to main sceen
        //
        $returnPage = $this->getBaseUrl().$this->getConfig('actions.change_password.return_page');
        header("Location: {$returnPage}?".$this->getMessageForUrl('success', 'Password changed successfully.'));

        exit();
    }

    protected function displayContent(): void
    {
        $this->loadTemplate('change_password.tpl', $this->pageContent);
    }
}

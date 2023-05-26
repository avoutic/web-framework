<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ChangeEmail extends PageAction
{
    public static function getFilter(): array
    {
        return [
            'email' => FORMAT_EMAIL,
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
        return 'Change email address';
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
        $email = $this->getInputVar('email');
        $this->pageContent['email'] = $this->getRawInputVar('email');

        // Check if this is a true attempt
        //
        if (!strlen($this->getInputVar('do')))
        {
            return;
        }

        // Check if email address is present
        //
        if (!strlen($email))
        {
            $this->addMessage('error', 'Please enter a correct e-mail address.', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');

            return;
        }

        // Change email
        //
        $user = $this->getAuthenticatedUser();
        $oldEmail = $user->email;

        // Send verification mail
        //
        $result = $user->sendChangeEmailVerify($email);

        if ($result == User::ERR_DUPLICATE_EMAIL)
        {
            $this->addMessage('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.');

            return;
        }

        if ($result != User::RESULT_SUCCESS)
        {
            $this->addMessage('error', 'Unknown errorcode: \''.$result."'", 'Please inform the administrator.');

            return;
        }

        // Redirect to verification request screen
        //
        $returnPage = $this->getBaseUrl().$this->getConfig('actions.change_email.return_page');
        header("Location: {$returnPage}?".$this->getMessageForUrl('success', 'Verification mail has been sent.', 'A verification mail has been sent. Please wait for the e-mail in your inbox and follow the instructions.'));

        exit();
    }

    protected function displayContent(): void
    {
        $this->loadTemplate('change_email.tpl', $this->pageContent);
    }
}

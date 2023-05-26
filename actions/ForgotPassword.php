<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\ContainerWrapper;
use WebFramework\Core\PageAction;

class ForgotPassword extends PageAction
{
    public static function getFilter(): array
    {
        $container = ContainerWrapper::get();

        $usernameFormat = FORMAT_USERNAME;
        if ($container->get('authenticator.unique_identifier') == 'email')
        {
            $usernameFormat = FORMAT_EMAIL;
        }

        return [
            'username' => $usernameFormat,
        ];
    }

    protected function getTitle(): string
    {
        return 'Forgot password';
    }

    protected function doLogic(): void
    {
        // Check if this is a true attempt
        //
        if (!strlen($this->getInputVar('do')))
        {
            return;
        }

        // Check if user present
        //
        $username = $this->getInputVar('username');
        if (!strlen($username))
        {
            $this->addMessage('error', 'Please enter a username.', '');

            return;
        }

        $baseFactory = $this->container->get(BaseFactory::class);

        // Retrieve user
        //
        $user = $baseFactory->getUserByUsername($username);

        if ($user !== false)
        {
            $user->sendPasswordResetMail();
        }

        // Redirect to main sceen
        //
        $loginPage = $this->getBaseUrl().$this->getConfig('actions.login.location');
        header("Location: {$loginPage}?".$this->getMessageForUrl('success', 'Reset link mailed to registered email account.'));

        exit();
    }

    protected function displayContent(): void
    {
        $this->loadTemplate('forgot_password.tpl', $this->pageContent);
    }
}

<?php

namespace WebFramework\Actions;

use WebFramework\Core\ContainerWrapper;
use WebFramework\Core\PageAction;
use WebFramework\Core\UserPasswordService;
use WebFramework\Repository\UserRepository;

class ForgotPassword extends PageAction
{
    protected UserPasswordService $userPasswordService;
    protected UserRepository $userRepository;

    public function init(): void
    {
        parent::init();

        $this->userPasswordService = $this->container->get(UserPasswordService::class);
        $this->userRepository = $this->container->get(UserRepository::class);
    }

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

        // Retrieve user
        //
        $user = $this->userRepository->getUserByUsername($username);

        if ($user !== null)
        {
            $this->userPasswordService->sendPasswordResetMail($user);
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

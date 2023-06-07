<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\UserEmailService;
use WebFramework\Entity\User;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\SecurityIteratorService;

class ChangeEmailVerify extends PageAction
{
    protected SecurityIteratorService $securityIteratorService;
    protected UserEmailService $userEmailService;
    protected UserRepository $userRepository;

    public function init(): void
    {
        parent::init();

        $this->securityIteratorService = $this->container->get(SecurityIteratorService::class);
        $this->userEmailService = $this->container->get(UserEmailService::class);
        $this->userRepository = $this->container->get(UserRepository::class);
    }

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
    protected function getUser(string $username): ?User
    {
        return $this->userRepository->getUserByUsername($username);
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
        if ($userId != $user->getId())
        {
            $this->deauthenticate();
            $loginPage = $this->getBaseUrl().$this->getConfig('actions.login.location');
            header("Location: {$loginPage}?".$this->getMessageForUrl('error', 'Other account', 'The link you used is meant for a different account. The current account has been logged off. Please try the link again.'));

            exit();
        }

        // Change email
        //
        $oldEmail = $user->getEmail();
        $securityIterator = $this->securityIteratorService->getFor($user);

        if (!isset($msg['params']) || !isset($msg['params']['iterator'])
            || $securityIterator != $msg['params']['iterator'])
        {
            header("Location: {$changePage}?".$this->getMessageForUrl('error', 'E-mail verification link expired'));

            exit();
        }

        try
        {
            $this->userEmailService->changeEmail($user, $email);
        }
        catch (DuplicateEmailException $e)
        {
            header("Location: {$changePage}?".$this->getMessageForUrl('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.'));

            exit();
        }

        // Invalidate old sessions
        //
        $this->invalidateSessions($user->getId());
        $this->authenticate($user);

        // Redirect to verification request screen
        //
        $returnPage = $this->getBaseUrl().$this->getConfig('actions.change_email.return_page');
        header("Location: {$returnPage}?".$this->getMessageForUrl('success', 'E-mail address changed successfully'));

        exit();
    }
}

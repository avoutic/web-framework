<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ChangeEmailVerify extends PageAction
{
    public static function get_filter(): array
    {
        return [
            'code' => '.*',
        ];
    }

    public static function get_permissions(): array
    {
        return [
            'logged_in',
        ];
    }

    protected function get_title(): string
    {
        return 'Change email address verification';
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function get_user(string $username): User|false
    {
        $factory = $this->framework->get_base_factory();

        return $factory->get_user_by_username($username);
    }

    protected function do_logic(): void
    {
        $change_page = $this->get_base_url().$this->get_config('actions.change_email.location');

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        $this->blacklist_verify(strlen($code), 'code-missing');

        $msg = $this->decode_and_verify_array($code);
        if (!$msg)
        {
            exit();
        }

        $this->blacklist_verify($msg['action'] == 'change_email', 'wrong-action', 2);

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: {$change_page}?".$this->get_message_for_url('error', 'E-mail verification link expired'));

            exit();
        }

        $user_id = $msg['id'];
        $email = $msg['params']['email'];
        $this->page_content['email'] = $email;

        // Only allow for current user
        //
        $user = $this->get_authenticated_user();
        if ($user_id != $user->id)
        {
            $this->deauthenticate();
            $login_page = $this->get_base_url().$this->get_config('actions.login.location');
            header("Location: {$login_page}?".$this->get_message_for_url('error', 'Other account', 'The link you used is meant for a different account. The current account has been logged off. Please try the link again.'));

            exit();
        }

        // Change email
        //
        $old_email = $user->email;

        if (!isset($msg['params']) || !isset($msg['params']['iterator'])
            || $user->get_security_iterator() != $msg['params']['iterator'])
        {
            header("Location: {$change_page}?".$this->get_message_for_url('error', 'E-mail verification link expired'));

            exit();
        }

        $result = $user->change_email($email);

        if ($result == User::ERR_DUPLICATE_EMAIL)
        {
            header("Location: {$change_page}?".$this->get_message_for_url('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.'));

            exit();
        }
        $this->verify($result == User::RESULT_SUCCESS, 'Unknown change email error');

        // Invalidate old sessions
        //
        $this->invalidate_sessions($user->id);
        $this->authenticate($user);

        // Redirect to verification request screen
        //
        $return_page = $this->get_base_url().$this->get_config('actions.change_email.return_page');
        header("Location: {$return_page}?".$this->get_message_for_url('success', 'E-mail address changed successfully'));

        exit();
    }
}

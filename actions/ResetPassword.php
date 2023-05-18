<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ResetPassword extends PageAction
{
    public static function get_filter(): array
    {
        return [
            'code' => '.*',
        ];
    }

    protected function get_title(): string
    {
        return 'Reset password';
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
        $forgot_password_page = $this->get_base_url().$this->get_config('actions.forgot_password.location');
        $login_page = $this->get_base_url().$this->get_config('actions.login.location');

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        $this->blacklist_verify(strlen($code), 'missing-code');

        $msg = $this->decode_and_verify_array($code);
        if (!$msg)
        {
            return;
        }

        $this->blacklist_verify($msg['action'] == 'reset_password', 'wrong-action', 2);

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: {$forgot_password_page}?".$this->get_message_for_url('error', 'Password reset link expired'));

            exit();
        }

        $user = $this->get_user($msg['username']);

        if ($user === false)
        {
            return;
        }

        if (!isset($msg['params']) || !isset($msg['params']['iterator'])
            || $user->get_security_iterator() != $msg['params']['iterator'])
        {
            header("Location: {$forgot_password_page}?".$this->get_message_for_url('error', 'Password reset link expired'));

            exit();
        }

        $user->send_new_password();

        // Invalidate old sessions
        //
        $this->invalidate_sessions($user->id);

        // Redirect to main sceen
        //
        header("Location: {$login_page}?".$this->get_message_for_url('success', 'Password reset', 'You will receive a mail with your new password'));

        exit();
    }
}

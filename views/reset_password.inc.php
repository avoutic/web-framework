<?php
require_once($includes.'base_logic.inc.php');

class PageResetPassword extends PageBasic
{
    static function get_filter()
    {
        return array(
                'code' => '.*',
                );
    }

    function get_title()
    {
        return "Reset password";
    }

    function do_logic()
    {
        // Check if code is present
        //
        $code = $this->get_input_var('code');
        $this->blacklist_verify(strlen($code), 'missing-code');

        $msg = $this->decode_and_verify_array($code);
        if (!$msg)
            return;

        $this->blacklist_verify($msg['action'] == 'reset_password', 'wrong-action', 2);

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: /forgot-password?".$this->get_message_for_url('error', 'Password reset link expired'));
            exit();
        }

        $factory = new BaseFactory();

        // Check user status
        //
        $user = $factory->get_user_by_username($msg['username']);
        $login_page = $this->get_config('pages.login.location');

        if ($user === FALSE)
            return;

        if (!$user->is_verified())
        {
            header("Location: ${login_page}?".$this->get_message_for_url('error', 'User is not verified.'));
            exit();
        }

        if (!isset($msg['params']) || !isset($msg['params']['iterator']) ||
            $user->get_security_iterator() != $msg['params']['iterator'])
        {
            header("Location: /forgot-password?".$this->get_message_for_url('error', 'Password reset link expired'));
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
};
?>

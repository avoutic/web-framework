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
        framework_add_bad_ip_hit();

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        if (!strlen($code))
        {
            framework_add_bad_ip_hit(2);
            return;
        }

        $msg = decode_and_verify_array($code);
        if (!$msg)
            return;

        if ($msg['action'] != 'reset_password')
        {
            framework_add_bad_ip_hit(4);
            return;
        }

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: /forgot-password?".add_message_to_url('error', 'Password reset link expired'));
            exit();
        }

        $factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $factory->get_user_by_username($msg['username']);
        $login_page = $this->config['pages']['login']['location'];

        if ($user === FALSE)
            return;

        if (!$user->is_verified())
        {
            header("Location: ${login_page}?".add_message_to_url('error', 'User is not verified.'));
            exit();
        }

        if (!isset($msg['params']) || !isset($msg['params']['iterator']) ||
            $user->get_security_iterator() != $msg['params']['iterator'])
        {
            header("Location: /forgot-password?".add_message_to_url('error', 'Password reset link expired'));
            exit();
        }

        $user->send_new_password();

        // Invalidate old sessions
        //
        $this->auth->invalidate_sessions($user->id);

        // Redirect to main sceen
        //
        header("Location: {$login_page}?".add_message_to_url('success', 'Password reset', 'You will receive a mail with your new password'));
        exit();
    }
};
?>

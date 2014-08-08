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
        if (!strlen($this->state['input']['code']))
        {
            framework_add_bad_ip_hit(2);
            return;
        }

        $str = decode_and_verify_string($this->state['input']['code']);
        if (!strlen($str))
        {
            framework_add_bad_ip_hit(4);
            return;
        }

        $msg = json_decode($str, true);
        if (!is_array($msg))
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

        if ($user === FALSE)
            return;

        if (!$user->is_verified())
        {
            header('Location: /?'.add_message_to_url('error', 'User is not verified.'));
            exit();
        }

        if (!isset($msg['params']) || !isset($msg['params']['iterator']) ||
            $user->get_config_value('reset_iterator', 0, 'account') != $msg['params']['iterator'])
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
        header("Location: /?".add_message_to_url('success', 'Password reset', 'You will receive a mail with your new password'));
        exit();
    }
};
?>

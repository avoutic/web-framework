<?php
require_once($includes.'base_logic.inc.php');

class PageSendVerify extends PageBasic
{
    static function get_filter()
    {
        return array(
                'code' => '.*',
                );
    }

    function get_title()
    {
        return "Request verification mail.";
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

        $str = decode_and_verify_string($code);
        if (!strlen($str))
        {
            framework_add_bad_ip_hit(4);
            return;
        }

        $msg = json_decode($str, true);
        if (!is_array($msg))
            return;

        if ($msg['action'] != 'send_verify')
        {
            framework_add_bad_ip_hit(4);
            return;
        }

        if ($msg['timestamp'] + 86400 < time())
        {
            // Expired
            header("Location: /login?".add_message_to_url('error', 'Send verification link expired', 'Please login again to request a new one</a>.'));
            exit();
        }

        $factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $factory->get_user_by_username($msg['username']);

        if ($user !== FALSE && !$user->is_verified())
            $user->send_verify_mail();

        // Redirect to main sceen
        //
        header("Location: /?".add_message_to_url('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.'));
        exit();
    }
};
?>

<?php
require_once($includes.'base_logic.inc.php');

class PageVerify extends PageBasic
{
    static function get_filter()
    {
        return array(
                'code' => '.*',
                );
    }

    function get_title()
    {
        return "Mail address verification";
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

        if ($msg['action'] != 'verify')
        {
            framework_add_bad_ip_hit(4);
            return;
        }

        if ($msg['timestamp'] + 86400 < time())
        {
            // Expired
            header("Location: /?".add_message_to_url('error', 'Verification mail expired', 'Please <a href="/login">request a new one</a> after logging in.'));
            exit();
        }

        $base_factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $base_factory->get_user_by_username($msg['username']);

        if ($user === FALSE)
            return;

        if ($user->is_verified())
        {
            header('Location: /?'.add_message_to_url('success', 'User already verified.'));
            exit();
        }

        $user->set_verified();

        // Redirect to main sceen
        //
        header("Location: /".$this->config['authenticator']['site_login_page']."?".add_message_to_url('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.')."&return_page=".$this->config['authenticator']['after_verify_page']);
        exit();
    }
};
?>

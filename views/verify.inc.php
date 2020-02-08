<?php
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

    function custom_after_verify_actions($user, $data)
    {
    }

    function do_logic()
    {
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
            $login_page = $this->config['authenticator']['site_login_page'];
            header("Location: ${login_page}?".add_message_to_url('error', 'Verification mail expired', 'Please <a href="/login">request a new one</a> after logging in.'));
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
            $after_verify_page = $this->config['registration']['after_verify_page'];
            header("Location: ${after_verify_page}?".add_message_to_url('success', 'User already verified.'));
            exit();
        }

        $user->set_verified();
        $this->custom_after_verify_actions($user, $msg['params']);

        // Redirect to main sceen
        //
        $login_page = $this->config['authenticator']['site_login_page'];
        header("Location: ${login_page}?".add_message_to_url('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.')."&return_page=".$this->config['registration']['after_verify_page']);
        exit();
    }
};
?>

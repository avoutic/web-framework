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
        $login_page = $this->config['pages']['login']['location'];
        $after_verify_page = $this->config['pages']['login']['after_verify_page'];

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        if (!strlen($code))
        {
            add_blacklist_entry('missing-code');
            return;
        }

        $msg = decode_and_verify_array($code);
        if (!$msg)
            return;

        if ($msg['action'] != 'verify')
        {
            add_blacklist_entry('wrong-action', 2);
            return;
        }

        if ($msg['timestamp'] + 86400 < time())
        {
            // Expired
            header("Location: ${login_page}?".add_message_to_url('error', 'Verification mail expired', 'Please <a href="/login">request a new one</a> after logging in.'));
            exit();
        }

        $base_factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $base_factory->get_user_by_username($msg['username']);

        if ($user === FALSE)
            return;

        if (!$user->is_verified())
        {
            $user->set_verified();
            $this->custom_after_verify_actions($user, $msg['params']);
        }

        // Redirect to main sceen
        //
        header("Location: ${login_page}?".add_message_to_url('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.')."&return_page=".urlencode($after_verify_page));
        exit();
    }
};
?>

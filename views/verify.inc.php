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
        $login_page = $this->get_config('pages.login.location');
        $after_verify_page = $this->get_config('pages.login.after_verify_page');

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        $this->blacklist_verify(strlen($code), 'missing-code');

        $msg = $this->decode_and_verify_array($code);
        if (!$msg)
        {
            header("Location: ${login_page}?".$this->get_message_for_url('error', 'Verification mail expired', 'Please <a href="/login">request a new one</a> after logging in.'));
            exit();
        }

        $this->blacklist_verify($msg['action'] == 'verify', 'wrong-action', 2);

        if ($msg['timestamp'] + 86400 < time())
        {
            // Expired
            header("Location: ${login_page}?".$this->get_message_for_url('error', 'Verification mail expired', 'Please <a href="/login">request a new one</a> after logging in.'));
            exit();
        }

        $base_factory = new BaseFactory();

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
        header("Location: ${login_page}?".$this->get_message_for_url('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.')."&return_page=".urlencode($after_verify_page));
        exit();
    }
};
?>

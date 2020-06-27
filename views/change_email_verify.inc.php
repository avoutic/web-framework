<?php
class PageChangeEmailVerify extends PageBasic
{
    static function get_filter()
    {
        return array(
                'code' => '.*',
                );
    }

    static function get_permissions()
    {
        return array(
                'logged_in'
                );
    }

    function get_title()
    {
        return "Change email address verification";
    }

    // Can be overriden for project specific user factories and user classes
    //
    function get_user($username)
    {
        $factory = new BaseFactory($this->global_info);
        $user = $factory->get_user_by_username($username);

        return $user;
    }

    function do_logic()
    {
        $change_page = $this->config['pages']['change_email']['location'];

        framework_add_bad_ip_hit();

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        if (!strlen($code))
        {
            framework_add_bad_ip_hit(2);
            exit();
        }

        $msg = decode_and_verify_array($code);
        if (!$msg)
            exit();

        if ($msg['action'] != 'change_email')
        {
            framework_add_bad_ip_hit(4);
            exit();
        }

        if ($msg['timestamp'] + 600 < time())
        {
            // Expired
            header("Location: {$change_page}?".add_message_to_url('error', 'E-mail verification link expired'));
            exit();
        }

        $user_id = $msg['id'];
        $email = $msg['params']['email'];
        $this->page_content['email'] = $email;

        // Only allow for current user
        //
        if ($user_id != $this->state['user_id'])
        {
            $this->auth->deauthenticate();
            $login_page = $this->config['pages']['login']['location'];
            header("Location: {$login_page}?".add_message_to_url('error', 'Other account', 'The link you used is meant for a different account. The current account has been logged off. Please try the link again.'));
            exit();
        }

        // Change email
        //
        $user = $this->get_user($this->state['username']);
        $old_email = $user->email;

        if (!isset($msg['params']) || !isset($msg['params']['iterator']) ||
            $user->get_security_iterator() != $msg['params']['iterator'])
        {
            header("Location: {$change_page}?".add_message_to_url('error', 'E-mail verification link expired'));
            exit();
        }

        $result = $user->change_email($email);

        if ($result == User::ERR_DUPLICATE_EMAIL)
        {
            header("Location: {$change_page}?".add_message_to_url('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.'));
            exit();
        }
        verify($result == User::RESULT_SUCCESS, 'Unknown change email error');

        // Invalidate old sessions
        //
        $this->auth->invalidate_sessions($user->id);
        $this->auth->set_logged_in($user);

        // Redirect to verification request screen
        //
        $return_page = $this->config['pages']['change_email']['return_page'];
        header("Location: ${return_page}?".add_message_to_url('success', 'E-mail address changed successfully'));
        exit();
    }
};
?>

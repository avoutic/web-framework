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
        // Continued calling of this page might result in lots of transactional e-mails
        // Until another limiting mechanism is in place, add a bad IP hit
        //
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

        if ($msg['action'] != 'send_verify')
        {
            framework_add_bad_ip_hit(4);
            return;
        }

        if ($msg['timestamp'] + 86400 < time())
        {
            $login_page = $this->config['pages']['login']['location'];

            // Expired
            header("Location: ${login_page}?".add_message_to_url('error', 'Send verification link expired', 'Please login again to request a new one.'));
            exit();
        }

        $base_factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $base_factory->get_user_by_username($msg['username']);

        if ($user !== false && !$user->is_verified())
            $user->send_verify_mail($msg['params']);

        $this->page_content['email'] = $user->email;

        // Redirect to main sceen
        //
        $after_verify_page = $this->config['pages']['send_verify']['after_verify_page'];

        header("Location: ${after_verify_page}?".add_message_to_url('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.'));
        exit();
    }

    function display_content()
    {
        $this->load_template('send_verify.tpl', $this->page_content);
    }
};
?>

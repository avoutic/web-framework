<?php
class PageForgotPassword extends PageBasic
{
    static function get_filter()
    {
        global $global_info;

        $username_format = FORMAT_USERNAME;
        if ($global_info['config']['registration']['email_is_username'])
            $username_format = FORMAT_EMAIL;

        return array(
                'username' => $username_format,
                );
    }

    function get_title()
    {
        return "Forgot password";
    }

    function do_logic()
    {
        // Check if this is a true attempt
        //
        if (!strlen($this->get_input_var('do')))
            return;

        framework_add_bad_ip_hit();

        // Check if user present
        //
        $username = $this->get_input_var('username');
        if (!strlen($username))
        {
            $this->add_message('error', 'Please enter a username.', '');
            return;
        }

        $base_factory = new BaseFactory($this->global_info);

        // Retrieve email address
        //
        $user = $base_factory->get_user_by_username($username);

        if ($user !== FALSE)
            $user->send_password_reset_mail();

        // Redirect to main sceen
        //
        $login_page = $this->config['authenticator']['site_login_page'];
        header("Location: ${login_page}?".add_message_to_url('success', 'Reset link mailed to registered email account.'));
        exit();
    }

    function display_header()
    {
        echo <<<HTML
  <meta name="robots" content="noindex,follow" />
HTML;
    }

    function display_content()
    {
        $this->load_template('forgot_password.tpl', $this->page_content);
    }
};
?>

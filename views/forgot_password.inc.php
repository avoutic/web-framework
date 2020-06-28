<?php
class PageForgotPassword extends PageBasic
{
    static function get_filter()
    {
        $username_format = FORMAT_USERNAME;
        if (WF::get_config('authenticator.unique_identifier') == 'email')
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

        // Check if user present
        //
        $username = $this->get_input_var('username');
        if (!strlen($username))
        {
            $this->add_message('error', 'Please enter a username.', '');
            return;
        }

        $base_factory = new BaseFactory();

        // Retrieve user
        //
        $user = $base_factory->get_user_by_username($username);

        if ($user !== FALSE)
            $user->send_password_reset_mail();

        // Redirect to main sceen
        //
        $login_page = $this->config['pages']['login']['location'];
        header("Location: ${login_page}?".$this->add_message_to_url('success', 'Reset link mailed to registered email account.'));
        exit();
    }

    function display_content()
    {
        $this->load_template('forgot_password.tpl', $this->page_content);
    }
};
?>

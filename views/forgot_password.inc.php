<?php
require_once($includes.'base_logic.inc.php');

class PageForgotPassword extends PageBasic
{
    static function get_filter()
    {
        return array(
                'username' => FORMAT_USERNAME,
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
        if (!strlen($this->state['input']['do']))
            return;

        framework_add_bad_ip_hit();

        // Check if user present
        //
        if (!strlen($this->state['input']['username'])) {
            $this->add_message('error', 'Please enter a username.', '');
            return;
        }

        $factory = new BaseFactory($this->global_info);

        // Retrieve email address
        //
        $user = $factory->get_user_by_username($this->state['input']['username']);

        if ($user !== FALSE)
            $user->send_password_reset_mail();

        // Redirect to main sceen
        //
        header("Location: /?".add_message_to_url('success', 'Reset link mailed to registered email account.'));
        exit();
    }

    function display_header()
    {
?>
  <meta name="robots" content="noindex,follow" />
<?
    }

    function display_content()
    {
        $this->load_template('forgot-password.tpl', $this->page_content);
    }
};
?>

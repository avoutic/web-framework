<?php
namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\WF;

class ForgotPassword extends PageAction
{
    static function get_filter(): array
    {
        $username_format = FORMAT_USERNAME;
        if (WF::get_config('authenticator.unique_identifier') == 'email')
            $username_format = FORMAT_EMAIL;

        return array(
                'username' => $username_format,
                );
    }

    protected function get_title(): string
    {
        return "Forgot password";
    }

    protected function do_logic(): void
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

        if ($user !== false)
            $user->send_password_reset_mail();

        // Redirect to main sceen
        //
        $login_page = $this->get_base_url().$this->get_config('actions.login.location');
        header("Location: ${login_page}?".$this->get_message_for_url('success', 'Reset link mailed to registered email account.'));
        exit();
    }

    protected function display_content(): void
    {
        $this->load_template('forgot_password.tpl', $this->page_content);
    }
};
?>

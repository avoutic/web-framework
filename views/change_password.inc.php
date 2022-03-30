<?php
class PageChangePassword extends PageBasic
{
    static function get_filter()
    {
        return array(
                'orig_password' => FORMAT_PASSWORD,
                'password' => FORMAT_PASSWORD,
                'password2' => FORMAT_PASSWORD,
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
        return "Change password";
    }

    // Can be overriden for project specific user factories and user classes
    //
    function get_user($username)
    {
        $factory = new BaseFactory();
        $user = $factory->get_user_by_username($username);

        return $user;
    }

    function customer_finalize_change()
    {
    }

    function do_logic()
    {
        // Check if this is a true attempt
        //
        if (!strlen($this->get_input_var('do')))
            return;

        $orig_password = $this->get_input_var('orig_password');
        $password = $this->get_input_var('password');
        $password2 = $this->get_input_var('password2');

        // Check if passwords are present
        //
        if (!strlen($orig_password))
        {
            $this->add_message('error', 'Please enter your current password.');
            return;
        }

        if (!strlen($password))
        {
            $this->add_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
            return;
        }

        if (!strlen($password2))
        {
            $this->add_message('error', 'Please enter the password verification.', 'Password verification should match your password.');
            return;
        }

        if ($password != $password2)
        {
            $this->add_message('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
            return;
        }

        $user = $this->get_user($this->get_authenticated('username'));
        $result = $user->change_password($orig_password, $password);

        if ($result == User::ERR_ORIG_PASSWORD_MISMATCH)
        {
            $this->add_message('error', 'Original password is incorrect.', 'Please re-enter your password.');
            return;
        }

        if ($result == User::ERR_NEW_PASSWORD_TOO_WEAK)
        {
            $this->add_message('error', 'New password is too weak.', 'Use at least 8 characters.');
            return;
        }

        if ($result != User::RESULT_SUCCESS)
        {
            $this->add_message('error', 'Unknown errorcode: \''.$result."'", "Please inform the administrator.");
            return;
        }

        $this->custom_finalize_change();

        // Invalidate old sessions
        //
        $this->invalidate_sessions($user->id);
        $this->authenticate($user);

        // Redirect to main sceen
        //
        $return_page = $this->get_base_url().$this->get_config('pages.change_password.return_page');
        header("Location: ${return_page}?".$this->get_message_for_url('success', 'Password changed successfully.'));
        exit();
    }

    function display_content()
    {
        $this->load_template('change_password.tpl', $this->page_content);
    }
};
?>

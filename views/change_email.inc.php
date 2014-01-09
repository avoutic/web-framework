<?php
require_once($includes.'base_logic.inc.php');

class PageChangeEmail extends PageBasic
{
    static function get_filter()
    {
        return array(
                'email' => FORMAT_EMAIL,
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
        return "Change email address";
    }

    function do_logic()
    {
        $email = $this->state['input']['email'];
        $this->page_content['email'] = $email;

        // Check if this is a true attempt
        //
        if (!strlen($this->state['input']['do']))
            return;

        // Check if email address is present
        //
        if (!strlen($email)) {
            $this->add_message('error', 'Please enter a correct Email address.', 'Email addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
            return;
        }

        // Change email
        //
        $factory = new BaseFactory($this->global_info);
        $user = $factory->get_user($this->state['user_id']);
        $old_email = $user->email;

        $result = $user->change_email($email);

        if ($result == User::ERR_DUPLICATE_EMAIL)
        {
            $this->add_message('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.');
            return;
        }
        if ($result != User::RESULT_SUCCESS)
        {
            $this->add_message('error', 'Unknown errorcode: \''.$result."'", "Please inform the administrator.");
            return;
        }

        // Logout user
        //
        $_SESSION['logged_in'] = false;
        $_SESSION['auth'] = array();

        session_destroy();

        // Send verification mail
        //
        $user->send_verify_mail();

        // Redirect to verification request screen
        //
        header('Location: /?'.add_message_to_url('success', 'Verification mail has been sent.','The verification mail has been sent. Please wait for the e-mail in your inbox and follow the instructions.'));
        exit();
    }

    function display_content()
    {
        $this->load_template('change-email.tpl', $this->page_content);
    }
};
?>

<?php
require_once($includes.'base_logic.inc.php');

class PageResetPassword extends PageBasic
{
    static function get_filter()
    {
        return array(
                'username' => FORMAT_USERNAME,
                'code' => FORMAT_VERIFY_CODE
                );
    }

    function get_title()
    {
        return "Reset password";
    }

    function do_logic()
    {
        framework_add_bad_ip_hit(5);

        // Check if username is present
        //
        if (!strlen($this->state['input']['username']))
            return;

        // Check if code is present
        //
        if (!strlen($this->state['input']['code']))
            return;

        $factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $factory->get_user_by_username($this->state['input']['username']);

        if (!$user->is_verified())
        {
            header('Location: /?'.add_message_to_url('error', 'User is not verified.'));
            exit();
        }

        $hash = $user->generate_verify_code('reset_password');

        if ($this->state['input']['code'] == $hash) {
            $user->send_new_password();

            // Redirect to main sceen
            //
            header("Location: /?".add_message_to_url('success', 'Password reset', 'You will receive a mail with your new password'));
            exit();
        }
    }
};
?>

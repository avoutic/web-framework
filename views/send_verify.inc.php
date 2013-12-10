<?php
require_once($includes.'base_logic.inc.php');

class PageSendVerify extends PageBasic
{
    static function get_filter()
    {
        return array(
                'username' => FORMAT_USERNAME,
                );
    }

    function get_title()
    {
        return "Request verification mail.";
    }

    function do_logic()
    {
        // Check if username is present
        //
        if (!strlen($this->state['input']['username']))
            return;

        $factory = new BaseFactory($this->global_info);

        // Check user status
        //
        $user = $factory->get_user_by_username($this->state['input']['username']);

        if ($user !== FALSE && !$user->is_verified())
            $user->send_verify_mail();

        // Redirect to main sceen
        //
        header("Location: /?".add_message_to_url('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.'));
        exit();
    }
};
?>

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

        if ($user->verified == 1)
        {
            header('Location: /?mtype=success&message='.urlencode('User already verified.'));
            exit();
        }

        $user->send_verify_mail();

        // Redirect to main sceen
        //
        header("Location: /?mtype=success&message=".urlencode("Verification mail sent")."&extra_message=".urlencode("Verification mail is sent. Please check your mailbox and follow the instructions."));
    }
};
?>

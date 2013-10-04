<?php
require_once($includes.'base_logic.inc.php');

class PageVerify extends PageBasic
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
        return "Mail address verification";
    }

    function do_logic()
    {
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

        if ($user->is_verified())
        {
            header('Location: /?'.add_message_to_url('success', 'User already verified.'));
            exit();
        }

        $hash = $user->generate_verify_code();

        if ($this->state['input']['code'] == $hash) {
            $user->set_verified();

            // Redirect to main sceen
            //
            header("Location: /".$this->config['authenticator']['site_login_page']."?".add_message_to_url('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.')."&return_page=".$this->config['authenticator']['after_verify_page']);
            exit();
        }
    }
};
?>

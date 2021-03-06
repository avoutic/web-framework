<?php
require_once(WF::$includes.'base_logic.inc.php');

class PageGrabIdentity extends PageBasic
{
    static function get_filter()
    {
        return array(
                'user_id' => '\d+'
                );
    }

    static function get_permissions()
    {
        return array(
                'logged_in',
                'user_management',
                'grab_identity',
                );
    }

    function get_title()
    {
        return "Grab identity";
    }

    function do_logic()
    {
        // Check if this is a login attempt
        //
        $user_id = $this->get_input_var('user_id');
        if (!strlen($user_id))
            return;

        $factory = new BaseFactory();

        // Log in user
        //
        $user = $factory->get_user($user_id);

        // Check if verified
        //
        if (!$user->is_verified()) {
            $code = $user->generate_verify_code('send_verify');

            $this->add_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="'.$this->get_base_url().'/send_verify?code='.$code.'">request a new one</a>.');
            return;
        }

        // Log in user
        //
        $this->authenticate($user);

        $page = $this->get_base_url().$this->get_config('pages.login.default_return_page');
        header("Location: {$page}?".$this->get_message_for_url('success', 'Login successful.'));
        exit();
    }
};
?>

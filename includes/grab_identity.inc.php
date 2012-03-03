<?php
require_once('base_logic.inc.php');
require_once('page_basic.inc.php');

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
                'grab_identity'
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
        if (!strlen($this->state['input']['user_id']))
            return;

        $factory = new BaseFactory($this->database);

        // Log in user
        //
        $user = $factory->get_user($this->state['input']['user_id'], 'UserFull');

        // Check if verified
        //
        if ($user->verified == 0) {
            $this->add_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="/send_verify?username='.$user->username.'">request a new one</a>.');
            return;
        }

        // Log in user
        //
        $success = true;

        $_SESSION['logged_in'] = true;
        :w

        $info = array();
        $info['user_id'] = $user->get_id();
        $info['username'] = $user->username;
        $info['name'] = $user->name;
        $info['permissions'] = array_merge(array('logged_in'), $user->permissions);
        $info['email'] = $user->email;
        $_SESSION['auth'] = $info;

        header("Location: /?mtype=success&message=".urlencode('Login successful.'));
    }
};
?>

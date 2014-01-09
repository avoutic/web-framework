<?php
require_once($includes.'base_logic.inc.php');

class PageChangeEmailVerify extends PageBasic
{
    static function get_filter()
    {
        return array(
                'code' => '.*',
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
        return "Change email address verification";
    }

    function do_logic()
    {
        framework_add_bad_ip_hit();

        // Check if code is present
        //
        if (!strlen($this->state['input']['code']))
        {
            framework_add_bad_ip_hit(2);
            exit();
        }

        $str = decode_and_verify_string($this->state['input']['code']);
        if (!strlen($str))
        {
            framework_add_bad_ip_hit(4);
            exit();
        }

        $msg = json_decode($str, true);
        if (!is_array($msg))
            exit();

        if ($msg['action'] != 'change_email')
        {
            framework_add_bad_ip_hit(4);
            exit();
        }

        $user_id = $msg['id'];
        $email = $msg['params']['email'];
        $this->page_content['email'] = $email;

        // Change email
        //
        $factory = new BaseFactory($this->global_info);
        $user = $factory->get_user($user_id);
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

        // Change session e-mail
        //
        $_SESSION['auth']['email'] = $email;

        // Redirect to verification request screen
        //
        header('Location: /account?'.add_message_to_url('success', 'E-mail address changed successfully'));
        exit();
    }
};
?>

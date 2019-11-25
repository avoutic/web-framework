<?php
class PageRegister extends Pagebasic
{
    static function custom_get_filter()
    {
    }

    static function get_filter()
    {
        $custom_filter = static::custom_get_filter();

        $base_filter = array(
                'username' => FORMAT_USERNAME,
                'password' => FORMAT_PASSWORD,
                'password2' => FORMAT_PASSWORD,
                'email' => FORMAT_EMAIL,
                'accept_terms' => '0|1',
                );

        return array_merge($base_filter, $custom_filter);
    }

    function get_title()
    {
        return "Register new account";
    }

    function get_onload()
    {
        return "$('#username').focus();";
    }

    function custom_prepare_page_content()
    {
    }

    function custom_value_check()
    {
    }

    function custom_finalize_create($user)
    {
    }

    function do_logic()
    {
        $email_is_username = $this->config['registration']['email_is_username'];

        $email = $this->state['input']['email'];
        $password = $this->state['input']['password'];
        $password2 = $this->state['input']['password2'];

        if ($email_is_username)
            $username = $email;
        else
            $username = $this->state['input']['username'];

        $accept_terms = $this->state['input']['accept_terms'];

        $this->page_content['username'] = $this->get_raw_input_var('username');
        $this->page_content['password'] = $password;
        $this->page_content['password2'] = $password2;
        $this->page_content['email'] = $this->get_raw_input_var('email');
        $this->page_content['accept_terms'] = $accept_terms;

        $this->custom_prepare_page_content();

        // Check if already logged in
        //
        if ($this->state['logged_in'])
            return;

        // Check if this is a true attempt
        //
        if (!strlen($this->state['input']['do']))
            return;

        $success = true;

        // Check if required values are present
        //
        if (!$email_is_username && !strlen($username)) {
            $this->add_message('error', 'Please enter a correct username.', 'Usernames can contain letters, digits and underscores.');
            $success = false;
        }

        if (!strlen($password)) {
            $this->add_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
            $success = false;
        }

        if (!strlen($password2)) {
            $this->add_message('error', 'Please enter the password verification.', 'Password verification should match password.');
            $success = false;
        }

        if (strlen($password) && strlen($password2) && $password != $password2) {
            $this->add_message('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
            $success = false;
        }

        if (!strlen($email)) {
            $this->add_message('error', 'Please enter a correct e-mmail address.', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
            $success = false;
        }

        if ($accept_terms != 1) {
            $this->add_message('error', 'Please accept our Terms.', 'To register for our site you need to accept our Privacy Policy and our Terms of Service.');
            $success = false;
        }

        if ($this->custom_value_check() !== true)
            $success = false;

        if (!$success)
            return;

        // Check if name already exists
        //
        $result = $this->database->Query('SELECT id FROM users WHERE username = ?',
                array($username));

        verify($result->RecordCount() <= 1, 'Too many results for username: '.$username);

        if ($result->RecordCount() == 1)
        {
            if ($email_is_username)
                $this->add_message('error', 'E-mail already registered.', 'An account has already been registerd with this e-mail address. <a href="/forgot-password">Forgot your password?</a>');
            else
                $this->add_message('error', 'Username already exists.', 'This username has already been taken. Please enter another username.');

            return;
        }

        // Add account
        //
        $base_factory = new BaseFactory($this->global_info);
        $result = $base_factory->create_user($username, $password, $email, time());
        verify($result !== false, 'Failed to create user');

        $this->custom_finalize_create($result);
        $this->post_create_actions($result);
    }

    function post_create_actions($user)
    {
        // Send mail to administrator
        //
        SenderCore::send_raw(MAIL_ADDRESS, SITE_NAME.": User '".$user->username."' registered.",
                "The user with username '".$user->username."' registered.\n".
                "E-mail is: '".$user->email.".");

        $msg = array(
            'action' => 'send_verify',
            'username' => $user->username,
            'timestamp' => time(),
        );
        $code = encode_and_auth_string(json_encode($msg));
        $send_verify_url = '/send-verify?code='.$code;

        // Redirect to verification request screen
        //
        header("Location: ".$send_verify_url);
        exit();
    }

    function display_content()
    {
        $this->load_template('register_account.tpl', $this->page_content);
    }
};
?>

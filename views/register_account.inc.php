<?php
require_once($includes.'recaptcha.inc.php');

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
                'g-recaptcha-response' => '.*',
                );

        return array_merge($base_filter, $custom_filter);
    }

    function check_sanity()
    {
        $recaptcha_config = $this->config['security']['recaptcha'];
        verify(strlen($recaptcha_config['site_key']), 'Missing reCAPTCHA Site Key');
        verify(strlen($recaptcha_config['secret_key']), 'Missing reCAPTCHA Secret Key');
    }

    function get_title()
    {
        return "Register new account";
    }

    function get_onload()
    {
        return "$('#username').focus();";
    }

    function get_after_verify_data()
    {
        return array();
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

        $email = $this->get_input_var('email');
        $password = $this->get_input_var('password');
        $password2 = $this->get_input_var('password2');

        if ($email_is_username)
            $username = $email;
        else
            $username = $this->get_input_var('username');

        $accept_terms = $this->get_input_var('accept_terms');

        $this->page_content['username'] = $this->get_raw_input_var('username');
        $this->page_content['password'] = $password;
        $this->page_content['password2'] = $password2;
        $this->page_content['email'] = $this->get_raw_input_var('email');
        $this->page_content['accept_terms'] = $accept_terms;
        $this->page_content['recaptcha_site_key'] = $this->config['security']['recaptcha']['site_key'];

        $this->custom_prepare_page_content();

        // Check if already logged in
        //
        if ($this->state['logged_in'])
            return;

        // Check if this is a true attempt
        //
        if (!strlen($this->get_input_var('do')))
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

        $recaptcha_response = $this->get_input_var('g-recaptcha-response');

        if (!strlen($recaptcha_response))
        {
            $this->add_message('error', 'CAPTCHA required', 'Due to possible brute force attacks on this username, filling in a CAPTCHA is required for checking the password!');
            $success = false;
            return;
        }
        else
        {
            $recaptcha = new Recaptcha($this->global_info);
            $result = $recaptcha->verify($recaptcha_response);

            if ($result != true)
            {
                $this->add_message('error', 'The CAPTCHA code entered was incorrect.');
                $success = false;
                return;
            }
        }

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
            'after_verify_data' => $this->get_after_verify_data(),
        );
        $code = encode_and_auth_string(json_encode($msg));
        $send_verify_url = '/send-verify?code='.$code;

        // Redirect to verification request screen
        //
        header("Location: ".$send_verify_url);
        exit();
    }

    function display_header()
    {
        echo <<<HTML
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
    }

    function display_content()
    {
        $this->load_template('register_account.tpl', $this->page_content);
    }
};
?>

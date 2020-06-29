<?php
require_once(WF::$includes.'base_logic.inc.php');
require_once(WF::$includes.'recaptcha.inc.php');

class PageLogin extends PageBasic
{
    static function get_filter()
    {
        $username_format = FORMAT_USERNAME;
        if (WF::get_config('authenticator.unique_identifier') == 'email')
            $username_format = FORMAT_EMAIL;

        return array(
                'return_page' => FORMAT_RETURN_PAGE,
                'return_query' => FORMAT_RETURN_QUERY,
                'username' => $username_format,
                'password' => FORMAT_PASSWORD,
                'g-recaptcha-response' => '.*',
                );
    }

    function check_sanity()
    {
        $login_config = $this->get_config('pages.login');
        $bruteforce_protection = $login_config['bruteforce_protection'];

        if ($bruteforce_protection)
        {
            $recaptcha_config = $this->get_config('security.recaptcha');
            $this->:verify(strlen($recaptcha_config['site_key']), 'Missing reCAPTCHA Site Key');
            $this->:verify(strlen($recaptcha_config['secret_key']), 'Missing reCAPTCHA Secret Key');
        }
    }

    function get_title()
    {
        return "Login";
    }

    function get_canonical()
    {
        return $this->page_content['login_page'];
    }

    function get_onload()
    {
        return "$('#inputUsername').focus();";
    }

    // Can be overriden for project specific user factories and user classes
    //
    function get_user($username)
    {
        $factory = new BaseFactory();

        $user = $factory->get_user_by_username($username);

        return $user;
    }

    function do_logic()
    {
        $return_page = $this->get_input_var('return_page');
        $return_query = $this->get_input_var('return_query');

        $this->page_content['return_query'] = $return_query;
        $this->page_content['username'] = $this->get_raw_input_var('username');
        $this->page_content['recaptcha_needed'] = false;
        $this->page_content['recaptcha_site_key'] = $this->get_config('security.recaptcha.site_key');

        if (!strlen($return_page) || substr($return_page, 0, 2) == '//')
            $return_page = $this->get_config('pages.login.default_return_page');

        if (substr($return_page, 0, 1) != '/')
            $return_page = '/'.$return_page;

        $this->page_content['return_page'] = $return_page;
        $this->page_content['login_page'] = $this->get_config('pages.login.location');
        $send_verify_page = $this->get_config('pages.login.send_verify_page');

        // Check if already logged in and redirect immediately
        if ($this->is_authenticated())
        {
            header("Location: ".$return_page."?".$return_query);
            exit();
        }

        // Check if this is a login attempt
        //
        if (!strlen($this->get_input_var('do')))
            return;

        // Check if username and password are present
        //
        if (!strlen($this->get_input_var('username')))
        {
            $this->add_message('error', 'Please enter a valid username.');
            return;
        }
        if (!strlen($this->get_input_var('password')))
        {
            $this->add_message('error', 'Please enter your password.');
            return;
        }

        // Log in user
        //
        $user = $this->get_user($this->get_input_var('username'));

        if ($user === FALSE)
        {
            $this->add_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            $this->add_blacklist_entry('unknown-username');
            return;
        }

        $bruteforce_protection = $this->get_config('pages.login.bruteforce_protection');
        if ($user->failed_login > 5 && $bruteforce_protection)
        {
            $recaptcha_response = $this->get_input_var('g-recaptcha-response');
            $this->page_content['recaptcha_needed'] = true;

            if (!strlen($recaptcha_response))
            {
                $this->add_message('error', 'CAPTCHA required', 'Due to possible brute force attacks on this username, filling in a CAPTCHA is required for checking the password!');
                return;
            }

            $recaptcha = new Recaptcha();
            $result = $recaptcha->verify_response($recaptcha_response);

            if ($result != true)
            {
                $this->add_message('error', 'The CAPTCHA code entered was incorrect.');
                return;
            }
        }

        if (!$user->check_password($this->get_input_var('password')))
        {
            $this->add_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            $this->add_blacklist_entry('wrong-password');
            return;
        }

        // Check if verified
        //
        if (!$user->is_verified())
        {
            $code = $user->generate_verify_code('send_verify');

            $this->add_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="'.$send_verify_page.'?code='.$code.'">request a new one</a>.');
            return;
        }

        // Log in user
        //
        $info = $this->authenticate($user);

        header("Location: ".$return_page."?".$return_query."&".$this->get_message_for_url('success', 'Login successful.'));
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
        $this->load_template('login.tpl', $this->page_content);
    }
};
?>

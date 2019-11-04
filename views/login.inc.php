<?php
require_once($includes.'base_logic.inc.php');
require_once($includes.'securimage/securimage.php');

class PageLogin extends PageBasic
{
    static function get_filter()
    {
        return array(
                'return_page' => FORMAT_RETURN_PAGE,
                'return_query' => FORMAT_RETURN_QUERY,
                'username' => FORMAT_USERNAME,
                'password' => FORMAT_PASSWORD,
                'captcha' => '.*',
                );
    }

    function get_title()
    {
        return "Login";
    }

    function get_onload()
    {
        return "$('#inputUsername').focus();";
    }

    function do_logic()
    {
        $return_page = $this->state['input']['return_page'];
        $return_query = $this->state['input']['return_query'];

        $this->page_content['return_query'] = $return_query;
        $this->page_content['username'] = $this->state['input']['username'];
        $this->page_content['captcha_needed'] = false;

        if (!strlen($return_page) || substr($return_page, 0, 2) == '//')
            $return_page = $this->config['authenticator']['default_login_return'];

        $this->page_content['return_page'] = $return_page;
        $this->page_content['login_page'] = $this->config['authenticator']['site_login_page'];

        // Check if already logged in and redirect immediately
        if ($this->state['logged_in'])
        {
            header("Location: ".$return_page."?return_query=".$return_query);
            exit();
        }

        // Check if this is a login attempt
        //
        if (!strlen($this->state['input']['do']))
            return;

        // Check if javascript is enabled
        //
        if (!strlen($this->state['input']['password']))
        {
            $this->add_message('error', 'Javascript is disabled.', 'Javascript is disabled or is not allowed. It is not possible to continue without Javascript.');
            return;
        }

        // Check if username and password are present
        //
        if (!strlen($this->state['input']['username'])) {
            $this->add_message('error', 'Please enter a correct username.', 'Usernames can contain letters, digits, dots, hyphens and underscores.');
            return;
        }
        if (!strlen($this->state['input']['password'])) {
            $this->add_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
            return;
        }

        $factory = new BaseFactory($this->global_info);

        // Log in user
        //
        $user = $factory->get_user_by_username($this->state['input']['username']);

        if ($user === FALSE)
        {
            $this->add_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            framework_add_bad_ip_hit();
            return;
        }

        if ($user->failed_login > 5)
        {
            $captcha = $this->state['input']['captcha'];
            $this->page_content['captcha_needed'] = true;

            if (!strlen($captcha))
            {
                $this->add_message('error', 'CAPTCHA required', 'Due to possible brute force attacks on this username, filling in a CAPTCHA is required for checking the password!');
                return;
            }

            $securimage = new Securimage();
            if ($securimage->check($captcha) !== TRUE) {
                $this->add_message('error', 'The CAPTCHA code entered was incorrect.');
                return;
            }
        }

        if (!$user->check_password($this->state['input']['password'])) {
            $this->add_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            framework_add_bad_ip_hit();
            return;
        }

        // Check if verified
        //
        if (!$user->is_verified()) {
            $code = $user->generate_verify_code('send_verify');

            $this->add_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="/send_verify?code='.$code.'">request a new one</a>.');
            return;
        }

        // Log in user
        //
        $info = $this->auth->set_logged_in($user);

        header("Location: ".$return_page."?return_query=".$return_query."&".add_message_to_url('success', 'Login successful.'));
        exit();
    }

    function display_header()
    {
?>
  <meta name="robots" content="noindex,follow" />
  <link rel="canonical" href="/login" />
<?
    }

    function display_content()
    {
        $this->load_template('login.tpl', $this->page_content);
    }
};
?>

<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\Recaptcha;
use WebFramework\Core\User;
use WebFramework\Core\WF;

class Login extends PageAction
{
    protected string $unique_identifier = '';

    public function __construct()
    {
        parent::__construct();

        $this->unique_identifier = $this->get_config('authenticator.unique_identifier');
    }

    public static function get_filter(): array
    {
        $username_format = FORMAT_USERNAME;
        if (WF::get_config('authenticator.unique_identifier') == 'email')
        {
            $username_format = FORMAT_EMAIL;
        }

        return [
            'return_page' => FORMAT_RETURN_PAGE,
            'return_query' => FORMAT_RETURN_QUERY,
            'username' => $username_format,
            'password' => FORMAT_PASSWORD,
            'g-recaptcha-response' => '.*',
        ];
    }

    protected function check_sanity(): void
    {
        $login_config = $this->get_config('actions.login');
        $bruteforce_protection = $login_config['bruteforce_protection'];

        if ($bruteforce_protection)
        {
            $recaptcha_config = $this->get_config('security.recaptcha');
            $this->verify(strlen($recaptcha_config['site_key']), 'Missing reCAPTCHA Site Key');
            $this->verify(strlen($recaptcha_config['secret_key']), 'Missing reCAPTCHA Secret Key');
        }
    }

    protected function get_title(): string
    {
        return 'Login';
    }

    protected function get_canonical(): string
    {
        return $this->page_content['login_page'];
    }

    protected function get_onload(): string
    {
        return "$('#inputUsername').focus();";
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function get_user(string $username): User|false
    {
        $factory = new BaseFactory();

        return $factory->get_user_by_username($username);
    }

    protected function custom_value_check(User $user): bool
    {
        return true;
    }

    protected function do_logic(): void
    {
        $return_page = $this->get_input_var('return_page');
        $return_query = $this->get_input_var('return_query');

        $this->page_content['return_query'] = $return_query;
        $this->page_content['username'] = $this->get_raw_input_var('username');
        $this->page_content['recaptcha_needed'] = false;
        $this->page_content['recaptcha_site_key'] = $this->get_config('security.recaptcha.site_key');

        if (!strlen($return_page) || substr($return_page, 0, 2) == '//')
        {
            $return_page = $this->get_config('actions.login.default_return_page');
        }

        if (substr($return_page, 0, 1) != '/')
        {
            $return_page = '/'.$return_page;
        }

        $this->page_content['return_page'] = $return_page;
        $this->page_content['login_page'] = $this->get_config('actions.login.location');
        $send_verify_page = $this->get_config('actions.login.send_verify_page');

        // Check if already logged in and redirect immediately
        if ($this->is_authenticated())
        {
            header('Location: '.$this->get_base_url().$return_page.'?'.$return_query.'&'.
                            $this->get_message_for_url('info', 'Already logged in'));

            exit();
        }

        // Check if this is a login attempt
        //
        if (!strlen($this->get_input_var('do')))
        {
            return;
        }

        // Check if username and password are present
        //
        if (!strlen($this->get_input_var('username')))
        {
            if ($this->unique_identifier == 'email')
            {
                $this->add_message('error', 'Please enter a valid email.');
            }
            else
            {
                $this->add_message('error', 'Please enter a valid username.');
            }

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

        if ($user === false)
        {
            if ($this->unique_identifier == 'email')
            {
                $this->add_message('error', 'E-mail and password do not match.', 'Please check if you entered the e-mail and/or password correctly.');
            }
            else
            {
                $this->add_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            }

            $this->add_blacklist_entry('unknown-username');

            return;
        }

        $bruteforce_protection = $this->get_config('actions.login.bruteforce_protection');
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

        if ($this->custom_value_check($user) !== true)
        {
            return;
        }

        // Check if verified
        //
        if (!$user->is_verified())
        {
            $code = $user->generate_verify_code('send_verify');

            $this->add_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="'.$this->get_base_url().$send_verify_page.'?code='.$code.'">request a new one</a>.');

            return;
        }

        // Log in user
        //
        $this->authenticate($user);

        header('Location: '.$this->get_base_url().$return_page.'?'.$return_query.'&'.$this->get_message_for_url('success', 'Login successful.'));

        exit();
    }

    protected function display_header(): void
    {
        echo <<<'HTML'
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
    }

    protected function display_content(): void
    {
        $this->load_template('login.tpl', $this->page_content);
    }
}

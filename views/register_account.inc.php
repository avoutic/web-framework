<?php
require_once(WF::$includes.'recaptcha.inc.php');

class PageRegister extends PageBasic
{
    /**
     * @return array<string, string>
     */
    static function custom_get_filter(): array
    {
        return array();
    }

    /**
     * @return array<string, string>
     */
    static function get_filter(): array
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

    protected function check_sanity(): void
    {
        $recaptcha_config = $this->get_config('security.recaptcha');
        $this->verify(strlen($recaptcha_config['site_key']), 'Missing reCAPTCHA Site Key');
        $this->verify(strlen($recaptcha_config['secret_key']), 'Missing reCAPTCHA Secret Key');
    }

    protected function get_title(): string
    {
        return "Register new account";
    }

    protected function get_onload(): string
    {
        return "$('#username').focus();";
    }

    /**
     * @return array<mixed>
     */
    protected function get_after_verify_data(): array
    {
        return array();
    }

    protected function custom_prepare_page_content(): void
    {
    }

    protected function custom_value_check(): bool
    {
        return true;
    }

    protected function custom_finalize_create(User $user): void
    {
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function create_user(string $username, string $password, string $email): User
    {
        $factory = new BaseFactory();
        $user = $factory->create_user($username, $password, $email, time());
        $this->verify($user !== false, 'Failed to create user');

        return $user;
    }

    protected function do_logic(): void
    {
        $identifier = $this->get_config('authenticator.unique_identifier');

        $email = $this->get_input_var('email');
        $password = $this->get_input_var('password');
        $password2 = $this->get_input_var('password2');

        if ($identifier == 'email')
            $username = $email;
        else
            $username = $this->get_input_var('username');

        $accept_terms = $this->get_input_var('accept_terms');

        $this->page_content['username'] = $this->get_raw_input_var('username');
        $this->page_content['password'] = $password;
        $this->page_content['password2'] = $password2;
        $this->page_content['email'] = $this->get_raw_input_var('email');
        $this->page_content['accept_terms'] = $accept_terms;
        $this->page_content['recaptcha_site_key'] = $this->get_config('security.recaptcha.site_key');

        $this->custom_prepare_page_content();

        // Check if already logged in
        //
        if ($this->is_authenticated())
        {
            // Redirect to default page
            //
            $return_page = $this->get_config('pages.login.default_return_page');
            header("Location: ".$this->get_base_url().$return_page."?".
                            $this->get_message_for_url('info', 'Already logged in'));
            exit();
        }

        // Check if this is a true attempt
        //
        if (!strlen($this->get_input_var('do')))
            return;

        $success = true;

        // Check if required values are present
        //
        if ($identifier == 'username' && !strlen($username)) {
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

        if (strlen($password) < 8)
        {
            $this->add_message('error', 'Password is too weak.', 'Use at least 8 characters.');
            $success = false;
        }

        if (!strlen($email)) {
            $this->add_message('error', 'Please enter a correct e-mail address.', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
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
            $this->add_message('error', 'CAPTCHA required', 'To prevent bots registering account en masse, filling in a CAPTCHA is required!');
            $success = false;
            return;
        }
        else
        {
            $recaptcha = new Recaptcha();
            $result = $recaptcha->verify_response($recaptcha_response);

            if ($result != true)
            {
                $this->add_message('error', 'The CAPTCHA code entered was incorrect.');
                $success = false;
                return;
            }
        }

        if (!$success)
            return;

        // Check if identifier already exists
        //
        if ($identifier == 'email')
        {
            $result = $this->query('SELECT id FROM users WHERE email = ?',
                    array($email));

            $this->verify($result->RecordCount() <= 1, 'Too many results for email: '.$email);

            if ($result->RecordCount() == 1)
            {
                $forgot_password_page = $this->get_base_url().$this->get_config('pages.forgot_password.location');

                $this->add_message('error', 'E-mail already registered.', 'An account has already been registered with this e-mail address. <a href="'.$forgot_password_page.'">Forgot your password?</a>');

                return;
            }
        }
        else
        {
            $result = $this->query('SELECT id FROM users WHERE username = ?',
                    array($username));

            $this->verify($result->RecordCount() <= 1, 'Too many results for username: '.$username);

            if ($result->RecordCount() == 1)
            {
                $this->add_message('error', 'Username already exists.', 'This username has already been taken. Please enter another username.');

                return;
            }
        }

        // Add account
        //
        $result = $this->create_user($username, $password, $email);

        $this->custom_finalize_create($result);
        $this->post_create_actions($result);
    }

    /**
     * @return never
     */
    protected function post_create_actions(User $user): void
    {
        // Send mail to administrator
        //
        SenderCore::send_raw($this->get_config('sender_core.default_sender'),
                $this->get_config('site_name').": User '".$user->username."' registered.",
                "The user with username '".$user->username."' registered.\n".
                "E-mail is: '".$user->email."'.");

        $code = $user->generate_verify_code('send_verify', $this->get_after_verify_data());
        $send_verify_page = $this->get_config('pages.login.send_verify_page');
        $send_verify_url = $send_verify_page.'?code='.$code;

        // Redirect to verification request screen
        //
        header("Location: ".$this->get_base_url().$send_verify_url);
        exit();
    }

    protected function display_header(): void
    {
        echo <<<HTML
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
HTML;
    }

    protected function display_content(): void
    {
        $this->load_template('register_account.tpl', $this->page_content);
    }
};
?>

<?php
class PageRegister extends Pagebasic
{
    static function get_filter()
    {
        return array(
                'username' => FORMAT_USERNAME,
                'password' => FORMAT_PASSWORD,
                'password2' => FORMAT_PASSWORD,
                'name' => FORMAT_NAME,
                'email' => FORMAT_EMAIL,
                'privacy_policy' => '0|1',
                'do' => 'yes'
                );
    }

    function get_title()
    {
        return "Register new account";
    }

    function get_onload()
    {
        return "$('#username').focus();";
    }

    function do_logic()
    {
        $username = $this->state['input']['username'];
        $password = $this->state['input']['password'];
        $password2 = $this->state['input']['password2'];
        $name = $this->state['input']['name'];
        $email = $this->state['input']['email'];
        $privacy_policy = $this->state['input']['privacy_policy'];

        $this->page_content['username'] = $username;
        $this->page_content['password'] = $password;
        $this->page_content['password2'] = $password2;
        $this->page_content['name'] = $name;
        $this->page_content['email'] = $email;
        $this->page_content['privacy_policy'] = $privacy_policy;

        // Check if already logged in
        //
        if ($this->state['logged_in'])
            return;

        // Check if this is a true attempt
        //
        if (!strlen($this->state['input']['do']))
            return;

        // Check if javascript is enabled
        //
        if (!strlen($password))
        {
            $this->add_message('error', 'Javascript is disabled.', 'Javascript is disabled or is not allowed. It is not possible to continue without Javascript.');
            return;
        }

        // Check if username and password are present
        //
        if (!strlen($username)) {
            $this->add_message('error', 'Please enter a correct username.', 'Usernames can contain letters, digits and underscores.');
            return;
        }

        if (!strlen($password) || $password == EMPTY_PASSWORD_HASH_SHA1) {
            $this->add_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
            return;
        }

        if (!strlen($password2) || $password2 == EMPTY_PASSWORD_HASH_SHA1) {
            $this->add_message('error', 'Please enter the password verification.', 'Password verification should match password.');
            return;
        }

        if ($password != $password2) {
            $this->add_message('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
            return;
        }

        if (!strlen($name)) {
            $this->add_message('error', 'Please enter a correct name.', 'Names can contain letters, digits, hyphens, spaces and underscores.');
            return;
        }

        if (!strlen($email)) {
            $this->add_message('error', 'Please enter a correct Email address.', 'Email addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
            return;
        }

        if ($privacy_policy != 1) {
            $this->add_message('error', 'Please accept our Privacy Policy.', 'To register for our site you need to accept our Privacy Policy.');
            return;
        }

        // Check if name already exists
        //
        $result = $this->database->Query('SELECT id FROM users WHERE username = ?',
                array($username));

        if ($result->RecordCount() > 1)
            die("Too many results for username $username! Exiting!");

        if ($result->RecordCount() == 1) {
            $this->add_message('error', 'Username already exists.', 'Please enter a unique username.');
            return;
        }

        $solid_password = User::new_hash_from_password($password);

        // Add account
        //
        $result = $this->database->InsertQuery('INSERT INTO users (username, solid_password, name, email) VALUES (?,?,?,?)',
                    array($username,
                        $solid_password,
                        $name,
                        $email));
        if (FALSE === $result)
        {
            die("Failed to insert data! Exiting!");
        }

        // Send mail to administrator
        //
        log_mail(SITE_NAME.": User '".$username."' registered.",
                "The user with username '".$username."' registered.\n".
                "Name is '".$name."' and email is '".$email.".");

        // Redirect to verification request screen
        //
        header("Location: /send_verify?username=".$username);
    }

    function display_header()
    {
?>
<script src="base/sha1.js" type="text/javascript"></script>
<?
    }

    function display_content()
    {
        $this->load_template('register.tpl', $this->page_content);
    }
};
?>

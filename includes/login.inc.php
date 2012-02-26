<?php
require_once('base_logic.inc.php');
require_once('page_basic.inc.php');

class PageLogin extends PageBasic
{
    static function get_filter()
    {
        return array(
                'return_page' => FORMAT_RETURN_PAGE,
                'return_query' => FORMAT_RETURN_QUERY,
                'username' => FORMAT_USERNAME,
                'password' => FORMAT_PASSWORD,
                'do' => 'yes'
                );
    }

    function get_title()
    {
        return "Login";
    }

    function do_logic()
    {
        $this->page_content['username'] = $this->state['input']['username'];
        $this->page_content['return_page'] = $this->state['input']['return_page'];
        $this->page_content['return_query'] = $this->state['input']['return_query'];

        if (!strlen($this->page_content['return_page']))
            $this->page_content['return_page'] = DEFAULT_LOGIN_RETURN;

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

        $factory = new BaseFactory($this->database);

        // Log in user
        //
        $user = $factory->get_user_by_username($this->state['input']['username'], 'UserFull');

        $success = false;
        if ($user === FALSE || !$user->check_password($this->state['input']['password'])) {
            $this->add_message('error', 'Username and password do not match.', 'Please check if you entered the username and/or password correctly.');
            return;
        }

        // Check if verified
        //
        if ($user->verified == 0) {
            $this->add_message('error', 'Account not yet verified.', 'Account is not yet verified. Please check your mailbox for the verification e-mail and go to the presented link. If you have not received such a mail, you can <a href="/send_verify?username='.$this->state['input']['username'].'">request a new one</a>.');
            return;
        }

        // Log in user
        //
        $success = true;

        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user->get_id();
        $_SESSION['username'] = $user->username;
        $_SESSION['name'] = $user->name;
        $_SESSION['permissions'] = array_merge(array('logged_in'), $user->permissions);
        $_SESSION['email'] = $user->email;

        header("Location: /".$this->page_content['return_page']."?return_query=".$this->page_content['return_query']."&mtype=success&message=".urlencode('Login successful.'));
    }

    function display_header()
    {
        ?>
            <script src="base/sha1.js" type="text/javascript"></script>
            <?
    }

    function display_content()
    {
?>
<form method="post" class="login_form" action="/login" enctype="multipart/form-data" onsubmit="password.value = hex_sha1(password_helper.value); return true;">
	<fieldset class="login">
		<input type="hidden" name="do" value="yes"/>
        <input type="hidden" id="password" name="password" value=""/>
		<input type="hidden" name="return_page" value="<?=$this->page_content['return_page']?>"/>
		<input type="hidden" name="return_query" value="<?=$this->page_content['return_query']?>"/>
		<legend>Login form</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username" value="<?=$this->page_content['username']?>"/>
		</p>
		<p>
			<label class="left" for="password_helper">Password</label> <input type="password" class="field" id="password_helper" name="password_helper"/>
		</p>
		<div>
			<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
		</div>
	</fieldset>
</form>
<span class="login_form_links"><a class="login_forgot_password_link" href="/forgot_password">Forgot your password?</a><? if ($this->config['allow_registration']) echo "| <a class=\"login_register_link\" href=\"/register_account\">No account yet?</a>";?></span>
<?
    }
};
?>

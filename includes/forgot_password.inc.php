<?php
require_once('base_logic.inc.php');
require_once('page_basic.inc.php');

class PageForgotPassword extends PageBasic
{
    static function get_filter()
    {
        return array(
                'username' => FORMAT_USERNAME,
                'do' => 'yes'
                );
    }

    function get_title()
    {
        return "Forgot password";
    }

    function do_logic()
    {
        // Check if this is a true attempt
        //
        if (!strlen($this->state['input']['do']))
            return;

        // Check if user present
        //
        if (!strlen($this->state['input']['username'])) {
            $this->add_message('error', 'Please enter a username.', '');
            return;
        }

        $factory = new BaseFactory($this->database);

        // Retrieve email address
        //
        $user = $factory->get_user_by_username($this->state['input']['username'], 'UserBasic');

        if ($user !== FALSE)
            $user->send_new_password();

        // Redirect to main sceen
        //
        header("Location: /?mtype=success&message=".urlencode('New password mailed to registered email account.'));
    }

    function display_content()
    {
?>
<form method="post" class="forgot_password_form" action="/forgot_password">
	<fieldset class="register">
		<input type="hidden" name="do" value="yes"/>

		<legend>Forgot password</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username"/>
		</p>
		<div>
			<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Reset password" />
		</div>
	</fieldset>
</form>
<?
    }
};
?>

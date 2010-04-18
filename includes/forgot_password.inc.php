<?php
require_once('base_logic.inc.php');

function get_page_filter()
{
	return array(
		'username' => FORMAT_USERNAME,
		'do' => 'yes'
	);
}

function get_page_permissions()
{
	return array(
		);
}

function get_page_title()
{
	return "Forgot password";
}

function do_page_logic()
{
	global $state, $database;

	// Check if this is a true attempt
	//
	if (!strlen($state['input']['do']))
		return;

	// Check if user present
	//
	if (!strlen($state['input']['username'])) {
		set_message('error', 'Please enter a username.', '');
		return;
	}

    $factory = new BaseFactory($database);

	// Retrieve email address
	//
    $user = $factory->get_user_by_username($state['input']['username'], 'UserBasic');

    if ($user !== FALSE)
        $user->send_new_password();

	// Redirect to main sceen
	//
	header("Location: /?mtype=success&message=".urlencode('New password mailed to registered email account.'));
}

function display_header()
{
?>
<?
}

function display_page()
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
?>

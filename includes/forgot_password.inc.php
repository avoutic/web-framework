<?php
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

	// Retrieve email address
	//
	$result_check = $database->Query('SELECT email, name FROM users WHERE username=?',
			array($state['input']['username']));

	if ($result_check->RecordCount() == 1) {
		// Generate and store password
		//
		$new_pw = sha1("SuperSecretHashingKey*".mt_rand(0, mt_getrandmax())."*".$state['input']['username']."*".time());
		$new_pw = substr($new_pw, 0, 10);
		
		if (FALSE === $database->Query('UPDATE users SET password = ? WHERE username = ?',
				array(
					sha1($new_pw),
					$state['input']['username']
				)))
		{
			die("Failed to update data! Exiting!");
		}

		// Send mail to user
		//
		mail($result_check->fields[0],
			SITE_NAME." password reset mail",
	                "Dear ".$result_check->fields[1].",\n\n".
	                "We successfully reset the password for your account '".
			$state['input']['username']."' for ".SITE_NAME.".\n\n".
			"Your new password is: ".$new_pw."\n\n".
			"We advise you to login and change your password as soon ".
			"as possible.\n\n".
			"To login and change your password go to: http://".$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI'])."/change_password\n\n".
			"Best regards,\n".
			MAIL_FOOTER,
			"From: ".MAIL_ADDRESS."\r\n");
	}

	// Redirect to main sceen
	//
	header("Location: ?mtype=success&message=".urlencode('New password mailed to registered email account.'));
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

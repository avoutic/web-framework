<?php
function get_page_filter()
{
	return array(
		'orig_password' => FORMAT_PASSWORD,
		'password' => FORMAT_PASSWORD,
		'password2' => FORMAT_PASSWORD,
		'do' => 'yes'
	);
}

function get_page_permissions()
{
	return array(
		'logged_in'
		);
}

function get_page_title()
{
	return "Change password";
}

function do_page_logic()
{
	global $state, $database;

	// Check if this is a true attempt
	//
	if (!strlen($state['input']['do']))
		return;

	// Check if passwords are present
	//
	if (!strlen($state['input']['orig_password']) || $state['input']['orig_password'] == EMPTY_PASSWORD_HASH_SHA1) {
		set_message('error', 'Please enter original password.', 'Passwords can contain any printable character.');
		return;
	}

	if (!strlen($state['input']['password']) || $state['input']['password'] == EMPTY_PASSWORD_HASH_SHA1) {
		set_message('error', 'Please enter a password.', 'Passwords can contain any printable character.');
		return;
	}

	if (!strlen($state['input']['password2']) || $state['input']['password2'] == EMPTY_PASSWORD_HASH_SHA1) {
		set_message('error', 'Please enter the password verification.', 'Password verification should match password.');
		return;
	}

	if ($state['input']['password'] != $state['input']['password2']) {
		set_message('error', 'Passwords don\'t match.', 'Password and password verification should be the same.');
		return;
	}

	// Check if original password is correct
	//
	$result_check = $database->Query('SELECT id FROM users WHERE username=? AND password=?',
			array(
				$state['username'],
				$state['input']['orig_password']
			));

	if ($result_check->RecordCount() != 1) {
		set_message('error', 'Original password is incorrect.', 'Please re-enter correct password.');
		return;
	}

	// Change password
	//
	if (FALSE === $database->Query('UPDATE users SET password=? WHERE username=? AND password=?',
			array(
				$state['input']['password'],
				$state['username'], 
				$state['input']['orig_password']
			)))
	{
		die("Failed to update data! Exitting!");
	}

	// Redirect to main sceen
	//
	header("Location: ?mtype=success&message=".urlencode('Password changed successfully.'));
}

function display_header()
{
?>
<script src="sha1.js" type="text/javascript"></script>
<?
}

function display_page()
{
	global $state;
?>
<form method="post" class="contactform" action="?page=change_password" enctype="multipart/form-data" onsubmit="password.value = hex_sha1(password.value); password2.value = hex_sha1(password2.value); orig_password.value = hex_sha1(orig_password.value); return true;">
	<fieldset class="register">
		<input type="hidden" name="do" value="yes"/>
		<legend>Change password</legend>
		<p>
			<label class="left" for="orig_password">Original password</label> <input type="password" class="field" id="orig_password" name="orig_password"/>
		</p>
		<p>
			<label class="left" for="password">Password</label> <input type="password" class="field" id="password" name="password"/>
		</p>
		<p>
			<label class="left" for="password2">Password verification</label> <input type="password" class="field" id="password2" name="password2"/>
		</p>
	</fieldset>
	<p>
		<label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Change" />
	</p>
</form>
<?
}
?>

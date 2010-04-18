<?php
function get_page_filter()
{
	return array(
		'username' => FORMAT_USERNAME,
		'password' => FORMAT_PASSWORD,
		'password2' => FORMAT_PASSWORD,
		'name' => FORMAT_NAME,
		'email' => FORMAT_EMAIL,
		'do' => 'yes'
	);
}

function get_page_permissions()
{
	return array();
}

function get_page_title()
{
	return "Register new account";
}

function do_page_logic()
{
	global $state, $database;

	// Check if already logged in
	//
	if ($state['logged_in'])
		return;

	// Check if this is a true attempt
	//
	if (!strlen($state['input']['do']))
		return;

	// Check if username and password are present
	//
	if (!strlen($state['input']['username'])) {
		set_message('error', 'Please enter a correct username.', 'Usernames can contain letters, digits and underscores.');
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

	if (!strlen($state['input']['name'])) {
		set_message('error', 'Please enter a correct name.', 'Names can contain letters, digits, hyphens, spaces and underscores.');
		return;
	}

	if (!strlen($state['input']['email'])) {
		set_message('error', 'Please enter a correct Email address.', 'Email addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');
		return;
	}

	// Check if name already exists
	//
	$result = $database->Query('SELECT id FROM users WHERE username = ?',
		array($state['input']['username']));

	if ($result->RecordCount() > 1)
		die("Too many results for username $username! Exiting!");
	
	if ($result->RecordCount() == 1) {
		set_message('error', 'Username already exists.', 'Please enter a unique username.');
		return;
	}

	// Add account
	//
	if (FALSE === $database->Query('INSERT INTO users (username, password, name, email) VALUES (?,?,?,?)',
			array($state['input']['username'],
				$state['input']['password'],
				$state['input']['name'],
				$state['input']['email'])))
	{
		die("Failed to insert data! Exiting!");
	}

	// Send mail to administrator
	//
	mail(MAIL_ADDRESS, SITE_NAME.": User '".$state['input']['username']."' registered.",
		"The user with username '".$state['input']['username']."' registered.\n".
		"Name is '".$state['input']['name']."' and email is '".$state['input']['email'].".",
		"From: ".MAIL_ADDRESS."\r\n");
	
	// Redirect to verification request screen
	//
	header("Location: /send_verify?username=".$state['input']['username']);
}

function display_header()
{
?>
<script src="base/sha1.js" type="text/javascript"></script>
<?
}

function display_page()
{
	global $state;
?>
<form method="post" class="register_form" action="/register_account" enctype="multipart/form-data" onsubmit="password.value = hex_sha1(password.value); password2.value = hex_sha1(password2.value); return true;">
	<fieldset class="register">
		<input type="hidden" name="do" value="yes"/>
		<legend>Login Details</legend>
		<p>
			<label class="left" for="username">Username</label> <input type="text" class="field" id="username" name="username" value="<?=$state['input']['username']?>"/>
		</p>
		<p>
			<label class="left" for="password">Password</label> <input type="password" class="field" id="password" name="password"/>
		</p>
		<p>
			<label class="left" for="password2">Password verification</label> <input type="password" class="field" id="password2" name="password2"/>
		</p>
	</fieldset>
	<fieldset class="user_details">
		<legend>User Details</legend>
		<p>
			<label class="left" for="name">Name</label> <input type="text" class="field" id="name" name="name" value="<?=$state['input']['name']?>"/>
		</p>
		<p>
			<label class="left" for="email">E-mail</label> <input type="text" class="field" id="email" name="email" value="<?=$state['input']['email']?>"/>
		</p>
	</fieldset>
	<div>
		<label class="left">&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
	</div>
</form>
<?
}
?>
